<?php

namespace App\Config;

/**
 * Application Configuration Class
 *
 * This class provides centralized configuration settings for the Tourism Management System.
 */
class AppConfig
{
    /**
     * Default pagination limit for API responses
     */
    public const DEFAULT_PAGINATION_LIMIT = 15;

    /**
     * Maximum pagination limit for API responses
     */
    public const MAX_PAGINATION_LIMIT = 100;

    /**
     * Default image upload size limit in MB
     */
    public const MAX_IMAGE_UPLOAD_SIZE = 5;

    /**
     * Supported image formats for uploads
     */
    public const SUPPORTED_IMAGE_FORMATS = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Default cache duration in minutes
     */
    public const DEFAULT_CACHE_DURATION = 60;

    /**
     * Tour difficulty levels
     */
    public const TOUR_DIFFICULTY_LEVELS = [
        1 => 'Easy',
        2 => 'Moderate',
        3 => 'Difficult',
    ];

    /**
     * User types
     */
    public const USER_TYPES = [
        1 => 'Client',
        2 => 'Admin',
        3 => 'Guide',
        4 => 'HotelManager',
        5 => 'TaxiAgent',
        6 => 'RestaurantManager',
        7 => 'TravelAgencyRep',
    ];
}
