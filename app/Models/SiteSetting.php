<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'business_name',
        'logo_path',
        'primary_color',
        'hero_eyebrow',
        'hero_title',
        'hero_highlight',
        'hero_description',
        'booking_button_label',
        'services_title',
        'services_description',
        'phone',
        'email',
        'notification_email',
        'address',
        'logo_zoom',
        'logo_offset_x',
        'logo_offset_y',
        'hero_image_path',
        'hero_image_zoom',
        'hero_image_position_x',
        'hero_image_position_y',
    ];
}