<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function favoriteService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('favoriteService');
        }

        return new \App\Services\FavoriteService(
            new \App\Models\AdFavoriteModel()
        );
    }

    public static function authService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('authService');
        }

        return new \App\Services\AuthService(
            new \App\Models\UserModel(),
            static::jwtService(),
            static::uploadService(),
            static::userService()
        );
    }

    public static function userService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('userService');
        }

        return new \App\Services\UserService(
            new \App\Models\UserModel(),
            static::uploadService()
        );
    }

    public static function jwtService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('jwtService');
        }

        return new \App\Services\JWTService();
    }

    public static function uploadService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('uploadService');
        }

        return new \App\Services\UploadService();
    }

    public static function adService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('adService');
        }

        return new \App\Services\AdService();
    }

    public static function messageService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('messageService');
        }

        return new \App\Services\MessageService();
    }

    public static function reportService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('reportService');
        }

        return new \App\Services\ReportService();
    }

    public static function sellerService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('sellerService');
        }

        return new \App\Services\SellerService(
            new \App\Models\SellerProfileModel(),
            static::uploadService()
        );
    }

    public static function verificationService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('verificationService');
        }

        return new \App\Services\VerificationService(
            new \App\Models\VerificationModel()
        );
    }

    public static function fileService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('fileService');
        }

        return new \App\Services\FileService();
    }

    public static function moderationService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('moderationService');
        }

        return new \App\Services\ModerationService();
    }

    public static function referralCodeService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('referralCodeService');
        }
        $model = model('App\\Models\\ReferralCodeModel');
        $db = \Config\Database::connect();
        return new \App\Services\ReferralCodeService($model, $db);
    }

    public static function feedbackService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('feedbackService');
        }
        return new \App\Services\FeedbackService(new \App\Models\AdFeedbackModel());
    }
}
