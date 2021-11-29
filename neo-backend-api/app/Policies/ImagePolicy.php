<?php

namespace App\Policies;

use App\Models\Image;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// FIXME
class ImagePolicy {

  use HandlesAuthorization;

  /**
   * Determine whether the user can update the image.
   *
   * @param User $user
   * @param Image $image
   *
   * @return mixed
   */
  public function update(User $user, Image $image)
  {
    return $user->id === $image->user_id;
  }

  /**
   * Determine whether the user can reorder images in the room
   *
   * @param User $user
   * @param Image[] $images
   *
   * @return mixed
   */
  public function reorder(User $user, $images)
  {
    return collect($images)->every('user_id', $user->id);
  }

  /**
   * Determine whether the user can assign images to the room
   *
   * @param User $user
   * @param Image[] $images
   *
   * @return mixed
   */
  public function assign(User $user, $images)
  {
    return collect($images)->every('user_id', $user->id);
  }

  /**
   * Determine whether the user can delete the image.
   *
   * @param User $user
   * @param Image $image
   *
   * @return mixed
   */
  public function destroy(User $user, Image $image)
  {
    return $user->id === $image->user_id;
  }

}
