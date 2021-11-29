<?php

use App\Models\Currency;
use App\Models\UserProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration {

  private $list = [
    ["Euro", "EUR", "€"],
    ["US dollar", "USD", "$"],
    ["Japanese yen", "JPY", "¥"],
    ["Bulgarian lev", "BGN", "Лв"],
    ["Czech koruna", "CZK", "Kč"],
    ["Danish krone", "DKK", "kr"],
    ["Pounds sterling", "GBP", "£"],
    ["Hungarian forint", "HUF", "ft"],
    ["Lithuanian litas", "LTL", "LTL"],
    ["Latvian lats", "LVL", "LVL"],
    ["Polish zloty", "PLN", "zł"],
    ["Romanian leu", "RON", "lei"],
    ["Swedish krona", "SEK", "kr"],
    ["Swiss franc", "CHF", "CHF"],
    ["Norwegian krone", "NOK", "kr"],
    ["Croatian Kuna", "HRK", "kn"],
    ["Russian ruble", "RUB", "₽"],
    ["Turkish lira", "TRY", "₺"],
    ["Australian dollars", "AUD", "$"],
    ["Brazilian real", "BRL", "R$"],
    ["Canadian dollars", "CAD", "$"],
    ["Chinese yuan", "CNY", "¥"],
    ["Hong Kong dollar", "HKD", "HK$"],
    ["Indonesian rupiah", "IDR", "Rp"],
    ["Israeli shekel", "ILS", "₪"],
    ["Indian rupee", "INR", "₹"],
    ["South Korean won", "KRW", "₩"],
    ["Mexican peso", "MXN", "$"],
    ["Malaysian ringgit", "MYR", "RM"],
    ["New Zealand dollar", "NZD", "$"],
    ["Philippine peso", "PHP", "₱"],
    ["Singapore dollar", "SGD", "$"],
    ["Thai baht", "THB", "฿"],
    ["South african rand", "ZAR", "R"],
    ["Estonian Kroon", "EEK", "EEK"],
    ["Slovak koruna", "SKK", "SKK"],
    ["Egyptian pound", "EGP", "ج.م"],
    ["Serbian dinar", "RSD", "din"],
    ["Tunisian dinar", "TND", "د.ت"],
    ["Emirati dirham", "AED", "د.إ"],
    ["Chilean peso", "CLP", "$"],
    ["Argentine peso", "ARS", "$"],
    ["Lebanese pound", "LBP", "ل.ل."],
    ["Tanzanian shilling", "TZS", "TSh"],
    ["Moroccan dirham", "MAD", "DH"],
    ["Mauritian rupee", "MUR", "₨"],
    ["Namibian dollar", "NAD", "N$"],
    ["Peruvian sol", "PEN", "S"],
    ["Nigerian naira", "NGN", "₦"],
    ["Ukrainian hryvna", "UAH", "₴"],
    ["Colombian peso", "COP", "$"],
    ["Vietnamese dong", "VND", "₫"],
  ];

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('currencies', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->char('code', 3)->index();
      $table->string('symbol', 5)->nullable();
    });

    foreach($this->list as [$name, $code, $symbol]) {
      Currency::create(compact('name', 'code', 'symbol'))->save();
    }

    Schema::table('user_profiles', function (Blueprint $table) {
      $table->renameColumn('currency', 'currency_id');
    });

    $c = Currency::findByCode('EUR');
    UserProfile::query()->update(['currency_id' => $c->id]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('currencies');

    Schema::table('user_profiles', function (Blueprint $table) {
      $table->renameColumn('currency_id', 'currency');
    });
  }
}
