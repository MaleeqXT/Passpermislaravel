<?php

namespace App\Enums\V2\Admin\Promo;

enum PromoTypeEnum: string
{
    case PROMO = 'promo';
    case CUSTOMIZE_HOME = 'customize_home';
    case CUSTOMIZE_CODE = 'customize_code';
    case PROMO_USER = 'promo_user';
    case CUSTOMIZE_SETTINGS = 'customize_settings';
}
