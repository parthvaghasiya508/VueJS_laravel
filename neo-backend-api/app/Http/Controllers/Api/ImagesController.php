<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImageResource;
use App\Jobs\UpdateRoomImages;
use App\Managers\PMSManager;
use App\Models\Hotel;
use App\Models\GroupImage;
use App\Models\HotelImage;
use App\Models\Image;
use App\Models\RoomImage;
use App\Models\UserImage;
use Exception;
use http\Exception\InvalidArgumentException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

;

class ImagesController extends Controller {

  /**
   * Get images list with rooms list
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return AnonymousResourceCollection|Response|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    /** @var Hotel $hotel */
    $hotel = session()->get('hotel');
    $rooms = $manager->getRooms(true);
    $rooms = collect($rooms)->map(fn ($room) => Arr::only($room, ['id', 'pid', 'langs', 'images']));
    $images = ImageResource::collection($hotel->imagesWithRooms);
    return compact('rooms', 'images');
  }

  /**
   * Upload image with or without room attachment
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return ImageResource
   * @throws ValidationException
   */
  public function create(Request $request, PMSManager $manager)
  {
    $user = $this->user($request);
    /** @var Hotel $hotel */
    $hotel = session()->get('hotel');
    $this->validate($request, [
      'room'  => 'nullable|numeric',
      'image' => 'required|file|mimetypes:image/png,image/jpeg',
    ]);
    $room = $request->input('room');
    $image = Image::create($request->file('image'), $user, $hotel, $room);
    if (isset($room)) {
      UpdateRoomImages::dispatch($room, $hotel->id);
    }
    ImageResource::withoutWrapping();
    return ImageResource::make($image);
  }

  /**
   * Update image data
   *
   * @param Request $request
   * @param Image $image
   * @param PMSManager $manager
   *
   * @return array
   * @throws ValidationException
   * @throws AuthorizationException
   */
  public function update(Request $request, Image $image, PMSManager $manager)
  {
    $this->authorize('update', $image);
    /** @var Hotel $hotel */
    $hotel = session()->get('hotel');
    $this->validate($request, [
      'name'    => 'nullable|string|max:300',
      'rooms'   => 'sometimes|array',
      'rooms.*' => 'sometimes|numeric',
    ]);

    if (!$request->hasAny('name', 'rooms')) {
      throw new BadRequestHttpException();
    }

    if ($request->has('name')) {
      $image->display_name = $request->input('name');
      $image->save();
    }

    // build the list of rooms to update, if needed
    if ($request->has('rooms')) {
      $oldRooms = $image->rooms->pluck('room_id');
      $newRooms = collect($request->input('rooms'));
      $toRemove = $oldRooms->diff($newRooms);
      $toAdd = $newRooms->diff($oldRooms);
      $toUpdate = $toAdd->union($toRemove)->unique();
      if ($toRemove) {
        $image->rooms->whereIn('room_id', $toRemove)->each->delete();
      }
      if ($toAdd) {
        $toAdd->each(function ($room_id) use (&$image) {
          $image->addToRoom($room_id);
        });
      }
      foreach ($toUpdate as $room) {
        UpdateRoomImages::dispatch($room, $hotel->id);
      }
    }

//    return compact('toAdd', 'toRemove', 'toUpdate');
    return ['ok' => true];
  }

  /**
   * Reorder images in the room
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param string $room
   *
   * @return Response|array
   * @throws AuthorizationException
   * @throws ValidationException
   */
  public function reorder(Request $request, PMSManager $manager, string $room)
  {
    /** @var Hotel $hotel */
    $hotel = session()->get('hotel');
    $images = RoomImage::imagesForRoom($room, false);
    $this->authorize('reorder', [Image::class, $images->pluck('image')]);

    $this->validate($request, [
      'images'   => 'required|array',
      'images.*' => 'numeric',
    ]);
    $order = collect($request->input('images'));
    $newImages = Image::query()->whereIn('id', $order)->get();
    // check whether all passed images belong to this user
    $this->authorize('assign', [Image::class, $newImages]);
    // check whether we need to add/remove images to/from the room
    // support for inter-room images dragging on frontend
    $oldIds = $images->pluck('image.id');
    $newIds = $newImages->pluck('id');

    if (($toDel = $oldIds->diff($newIds))->count()) {
      // remove images from room
      $images->whereIn('image_id', $toDel)->each->delete();
      $images = $images->whereNotIn('image_id', $toDel);
    }
    if (($toAdd = $newIds->diff($oldIds))->count()) {
      // add images to room
      $toAdd->each(function ($iid) use ($room, &$images) {
        $images->add(RoomImage::createNew($iid, $room));
      });
    }

    foreach ($order as $idx => $image_id) {
      if ($obj = $images->firstWhere('image_id', $image_id)) {
        $obj->update(['display_order' => $idx + 1]);
      }
    }
    UpdateRoomImages::dispatch($room, $hotel->id);
    return ['ok' => true];
  }

  /**
   * Serves room image or hotel logo
   *
   * @param Request $request
   * @param string $hotel_id
   * @param string $code
   *
   * @return mixed
   */
  public function image(Request $request, $hotel_id, $code)
  {
    $cls = strlen($code) === 16 ? Image::class : HotelImage::class;
    $image = $cls::query()->firstWhere(compact('hotel_id', 'code'));
    if (!$image) {
      throw new NotFoundHttpException('Not found');
    }
    if ($request->hasHeader('If-Modified-Since')) {
      // images are created with unique codes and isn't updated later
      // so if the browser already has it cached then we respond with 304 Not Modified
      return response(null, Response::HTTP_NOT_MODIFIED);
    }

//    return Storage::disk('images')->response('0rGok7n1Oym8QYiztjcVk6VtUc6OnBNQ1cWMN9Ww.jpg', null);
    return $image->serve();
  }

  public function groupImage(Request $request, $code)
  {
    /** @var GroupImage $image */
    $image = GroupImage::query()->firstWhere(compact('code'));
    if (!$image) {
      throw new NotFoundHttpException('Not found');
    }
    if ($request->hasHeader('If-Modified-Since')) {
      // images are created with unique codes and isn't updated later
      // so if the browser already has it cached then we respond with 304 Not Modified
      return response(null, Response::HTTP_NOT_MODIFIED);
    }

    return $image->serve();
  }

  public function avatarImage(Request $request, $code)
  {
    /** @var UserImage $image */
    $image = UserImage::query()->firstWhere(compact('code'));
    if (!$image) {
      throw new NotFoundHttpException('Not found');
    }
    if ($request->hasHeader('If-Modified-Since')) {
      // images are created with unique codes and isn't updated later
      // so if the browser already has it cached then we respond with 304 Not Modified
      return response(null, Response::HTTP_NOT_MODIFIED);
    }

    return $image->serve();
  }

  /**
   * Delete image
   *
   * @param Request $request
   * @param Image $image
   * @param PMSManager $manager
   *
   * @return Response|array
   * @throws Exception
   */
  public function destroy(Request $request, Image $image, PMSManager $manager)
  {
    $this->authorize('destroy', $image);
    /** @var Hotel $hotel */
    $hotel = session()->get('hotel');
    $rooms = $image->rooms->pluck('room_id');
    $image->delete();
    foreach ($rooms as $room) {
      UpdateRoomImages::dispatch($room, $hotel->id);
    }
    return ['ok' => true];
  }
}
