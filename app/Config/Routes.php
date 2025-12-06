<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Routes API
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    // Préflight CORS géré par le filtre 'cors' (voir Config\Filters et Config\Cors)

    // Routes pour le boost d'annonce
    $routes->group('boost', function ($routes) {
        $routes->post('create-boosted-ad', '\App\Controllers\BoostController::createBoostedAd', ['filter' => 'auth']);
        $routes->post('boost-existing-ad/(:segment)', '\App\Controllers\BoostController::boostExistingAd/$1', ['filter' => 'auth']);
        $routes->post('confirm-payment/(:num)', '\App\Controllers\BoostController::confirmBoostPayment/$1');
        $routes->get('check-payment/(:num)', '\App\Controllers\BoostController::checkBoostPayment/$1', ['filter' => 'auth']);
        $routes->post('retry-payment/(:num)', '\App\Controllers\BoostController::retryPayment/$1', ['filter' => 'auth']);
        $routes->post('publish-free', '\App\Controllers\BoostController::publishFreeVersion', ['filter' => 'auth']);
        $routes->get('list-boosted-ads', '\App\Controllers\BoostController::listBoostedAds');
    });

    // Routes pour les packs de promotion (admin)
    $routes->group('promotion-packs', function ($routes) {
        $routes->get('/', 'PromotionPackController::index');
        $routes->get('(:num)', 'PromotionPackController::show/$1');
        $routes->post('/', 'PromotionPackController::create', ['filter' => 'admin']);
        $routes->put('(:num)', 'PromotionPackController::update/$1', ['filter' => 'admin']);
        $routes->delete('(:num)', 'PromotionPackController::delete/$1', ['filter' => 'admin']);
    });

    // Routes OPTIONS pour CORS preflight - Auth
    $routes->options('auth/register', 'AuthController::options');
    $routes->options('auth/login', 'AuthController::options');
    $routes->options('auth/google', 'AuthController::options');
    $routes->options('auth/facebook', 'AuthController::options');
    $routes->options('auth/forgot-password', 'AuthController::options');
    $routes->options('auth/reset-password', 'AuthController::options');
    $routes->options('auth/verify-identity', 'AuthController::options');
    $routes->options('auth/me', 'AuthController::options');

    // Routes OPTIONS pour CORS preflight - Categories
    $routes->options('categories', 'CategoryController::options');
    $routes->options('categories/stats', 'CategoryController::options');
    $routes->options('categories/(:any)', 'CategoryController::options');
    $routes->options('categories/(:num)/subcategories', 'CategoryController::options');

    // Routes OPTIONS pour CORS preflight - Subcategories
    $routes->options('subcategories/(:segment)/fields', 'AdsController::options');

    // Routes OPTIONS pour CORS preflight - Filters
    $routes->options('filters/by-subcategory/(:segment)', 'FilterController::options');

    // Routes OPTIONS pour CORS preflight - Files
    $routes->options('uploads/(:any)', 'FileController::options');
    
    // Route pour servir les fichiers uploadés
    $routes->get('uploads/(:segment)/(:any)', 'FileController::serveFile/$1/$2');

    // Routes OPTIONS pour CORS preflight - Autres endpoints
    $routes->options('users/me', 'UserController::options');
    $routes->options('seller-profiles', 'SellerProfileController::options');
    $routes->options('seller-profiles/me', 'SellerProfileController::options');
    $routes->options('seller-profiles/(:any)', 'SellerProfileController::options');
    $routes->options('ads', 'AdsController::options');
    $routes->options('ads/creation-data', 'AdsController::options');
    $routes->options('ads/(:any)', 'AdsController::options');
    $routes->options('ads/user/(:any)', 'AdsController::options');
    $routes->options('ads/category/(:any)', 'AdsController::options');
    $routes->options('ads/subcategory/(:any)', 'AdsController::options');
    $routes->options('ads/id/(:any)', 'AdsController::options');
    $routes->options('locations', 'LocationController::options');
    $routes->options('locations/(:any)', 'LocationController::options');
    $routes->options('payments', 'PaymentController::options');
    $routes->options('payments/(:any)', 'PaymentController::options');
    $routes->options('messages', 'MessageController::options');
    $routes->options('messages/(:any)', 'MessageController::options');
    $routes->options('messages/unread/count', 'MessageController::options');
    $routes->options('messages/ad/(:any)', 'MessageController::options');
    $routes->options('brands', 'BrandController::options');
    $routes->options('brands/(:any)', 'BrandController::options');
    $routes->options('reports', 'ReportController::options');
    $routes->options('reports/(:any)', 'ReportController::options');
    $routes->options('reports/admin/pending', 'ReportController::options');
    $routes->options('reports/stats', 'ReportController::options');
    $routes->options('referrals', 'ReferralController::options');
    $routes->options('referrals/(:any)', 'ReferralController::options');
    $routes->options('referrals/use', 'ReferralController::options');
    $routes->options('referrals/stats', 'ReferralController::options');
    $routes->options('admin/(:any)', 'AdminController::options');
    $routes->options('admin/referentials/(:any)', 'AdminReferentialController::options');
    $routes->options('admin/promotions/(:any)', 'AdminPromotionController::options');
    $routes->options('admin/reporting/(:any)', 'AdminReportingController::options');
    $routes->options('v1/users', 'UserController::options');
    $routes->options('v1/users/(:any)', 'UserController::options');
    $routes->options('users/(:any)', 'UserController::options');
    $routes->options('users/(:num)/verify-identity', 'UserController::options');
    $routes->options('users/(:num)/change-password', 'UserController::options');
    $routes->options('admin/users/(:num)/identity/request-changes', 'AdminController::options');

    // Routes OPTIONS pour CORS preflight - Admin Referral Codes
    $routes->options('admin/referralcodes', 'AdminReferentialController::options');
    $routes->options('admin/referralcodes/(:num)/filleuls', 'AdminReferentialController::options');
    $routes->options('admin/referralcodes/(:num)/activate', 'AdminReferentialController::options');
    $routes->options('admin/referralcodes/(:num)/deactivate', 'AdminReferentialController::options');

    // Routes OPTIONS pour CORS preflight - Admin Ads
    $routes->options('admin/ads', 'AdminController::options');
    $routes->options('admin/ads/pending', 'AdminController::options');
    $routes->options('admin/ads/(:num)/approve', 'AdminController::options');
    $routes->options('admin/ads/(:num)/reject', 'AdminController::options');

    // Routes d'authentification
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/google', 'AuthController::google');
    $routes->post('auth/facebook', 'AuthController::facebook');
    $routes->post('auth/forgot-password', 'AuthController::forgotPassword');
    $routes->post('auth/reset-password', 'AuthController::resetPassword');
    $routes->post('auth/verify-identity', 'AuthController::verifyIdentity', ['filter' => 'auth']);
    $routes->get('auth/me', 'AuthController::me', ['filter' => 'auth']);

    // Routes utilisateurs /me
    $routes->get('users/me', 'UserController::me', ['filter' => 'auth']);
    $routes->put('users/me', 'UserController::updateMe', ['filter' => 'auth']);
    $routes->post('users/me', 'UserController::updateMe', ['filter' => 'auth']); // Alternative pour FormData
    $routes->get('users/debug-me', 'UserController::debugMe', ['filter' => 'auth']);
    $routes->put('users/debug-me', 'UserController::debugMe', ['filter' => 'auth']);
    $routes->post('users/debug-me', 'UserController::debugMe', ['filter' => 'auth']);
    $routes->post('users/test-upload', 'UserController::testUpload', ['filter' => 'auth']);
    $routes->post('users/(:num)/verify-identity', 'UserController::verifyIdentity/$1', ['filter' => 'auth']);

    // Routes utilisateurs (API v1)
    $routes->group('v1', function ($routes) {
        $routes->resource('users', ['controller' => 'UserController', 'filter' => 'auth']);
        $routes->post('users/(:num)/verify-identity', 'UserController::verifyIdentity/$1', ['filter' => 'auth']);
        $routes->put('users/(:num)/change-password', 'UserController::changePassword/$1', ['filter' => 'auth']);



    });

    // Routes pour les annonces
    $routes->group('ads', function ($routes) {
        // Données de création d'annonce
        $routes->get('creation-data', 'AdsController::getCreationData');

        // Listing et recherche d'annonces
        $routes->get('/', 'AdsController::index');
        $routes->post('/', 'AdsController::create', ['filter' => 'auth']);

        // Feedbacks (placer AVANT les routes génériques post('(:any)'))
        $routes->get('(:num)/feedbacks', 'FeedbackController::listApprovedByAd/$1');
        $routes->get('(:num)/feedbacks/summary', 'FeedbackController::summaryByAd/$1');
        $routes->post('(:num)/feedbacks', 'FeedbackController::createForAd/$1', ['filter' => 'auth']);

        // Annonces par utilisateur - Stats de vues
        $routes->get('user/(:num)/views-stats', 'AdsController::getUserViewsStats/$1');

        // Annonces par utilisateur
        $routes->get('user/(:num)', 'AdsController::getByUser/$1');

        // Annonces par catégorie
        $routes->get('category/(:num)', 'AdsController::getByCategory/$1');

        // Annonces par sous-catégorie (accepte ID numérique ou slug)
        $routes->get('subcategory/(:segment)', 'AdsController::getBySubcategory/$1');

        // Annonce spécifique par slug ou ID (auto-détection)
        $routes->get('(:any)', 'AdsController::show/$1');

        // Annonce spécifique par ID (compatibilité explicite)
        $routes->get('id/(:num)', 'AdsController::getById/$1');

        // Statut d'une annonce (pour polling de paiement)
        $routes->get('(:num)/status', 'AdsController::getStatus/$1', ['filter' => 'auth']);

        // Modification et suppression d'annonce (slug ou ID auto-détecté)
        $routes->put('(:any)', 'AdsController::update/$1', ['filter' => 'auth']);
        $routes->patch('(:any)', 'AdsController::update/$1', ['filter' => 'auth']); // Alternative pour JSON
        $routes->post('(:any)', 'AdsController::update/$1', ['filter' => 'auth']); // Alternative pour multipart/form-data
        $routes->delete('(:any)', 'AdsController::delete/$1', ['filter' => 'auth']);
    });

    // Routes pour les champs de sous-catégories
    $routes->get('subcategories/(:segment)/fields', 'AdsController::getSubcategoryFields/$1');

    // Routes pour les catégories
    $routes->group('categories', function ($routes) {
        $routes->get('/', 'CategoryController::index');
        $routes->get('stats', 'CategoryController::getCategoriesWithStats');
        $routes->get('(:num)', 'CategoryController::show/$1');
        $routes->get('(:num)/subcategories', 'CategoryController::getSubcategories/$1');
    });

    // Routes pour les localisations
    $routes->group('locations', function ($routes) {
        $routes->get('/', 'LocationController::index');
        $routes->get('(:num)', 'LocationController::show/$1');
        $routes->post('/', 'LocationController::create');
        $routes->put('(:num)', 'LocationController::update/$1');
        $routes->delete('(:num)', 'LocationController::delete/$1');
    });

    // Routes pour les profils vendeurs
    $routes->group('seller-profiles', function ($routes) {
        $routes->get('/', 'SellerProfileController::index');
        $routes->get('me', 'SellerProfileController::me', ['filter' => 'auth']);
        $routes->put('me', 'SellerProfileController::updateMe', ['filter' => 'auth']);
        $routes->get('(:num)', 'SellerProfileController::getByUserId/$1');
        $routes->post('/', 'SellerProfileController::create', ['filter' => 'auth']);
        $routes->put('(:num)', 'SellerProfileController::update/$1', ['filter' => 'auth']);
        $routes->delete('(:num)', 'SellerProfileController::delete/$1', ['filter' => 'auth']);
    });

    // Routes pour les paiements
    $routes->group('payments', function ($routes) {
        $routes->post('/', 'PaymentController::create', ['filter' => 'auth']);
        $routes->get('(:num)', 'PaymentController::show/$1', ['filter' => 'auth']);
        $routes->get('/', 'PaymentController::index', ['filter' => 'auth']);
    });

    // Routes pour les messages
    $routes->group('messages', function ($routes) {
        $routes->get('/', 'MessageController::index', ['filter' => 'auth']);
        $routes->post('/', 'MessageController::create', ['filter' => 'auth']);
        $routes->get('(:num)', 'MessageController::show/$1', ['filter' => 'auth']);
        $routes->put('(:num)/read', 'MessageController::markAsRead/$1', ['filter' => 'auth']);
        $routes->delete('(:num)', 'MessageController::delete/$1', ['filter' => 'auth']);
        $routes->get('unread/count', 'MessageController::countUnread', ['filter' => 'auth']);
        $routes->get('ad/(:num)', 'MessageController::getAdMessages/$1', ['filter' => 'auth']);
    });

    // Suppression du doublon des routes 'ads' (consolidé ci-dessus)

    // Routes pour les marques
    $routes->group('brands', function ($routes) {
        $routes->get('/', 'BrandController::index');
        $routes->get('(:num)', 'BrandController::show/$1');
        $routes->post('/', 'BrandController::create', ['filter' => 'admin']);
        $routes->put('(:num)', 'BrandController::update/$1', ['filter' => 'admin']);
        $routes->delete('(:num)', 'BrandController::delete/$1', ['filter' => 'admin']);
    });

    // Suppression du doublon des routes 'messages' (consolidé ci-dessus)

    // Routes pour les signalements
    $routes->group('reports', function ($routes) {
        $routes->get('/', 'ReportController::index', ['filter' => 'auth']);
        $routes->post('/', 'ReportController::create', ['filter' => 'auth']);
        $routes->get('(:num)', 'ReportController::show/$1', ['filter' => 'auth']);
        $routes->put('(:num)/resolve', 'ReportController::resolve/$1', ['filter' => 'admin']);
        $routes->put('(:num)/dismiss', 'ReportController::dismiss/$1', ['filter' => 'admin']);
        $routes->get('admin/pending', 'ReportController::pending', ['filter' => 'admin']);
        $routes->get('stats', 'ReportController::stats', ['filter' => 'admin']);
    });

    // Routes pour les codes de parrainage
    $routes->group('referrals', function ($routes) {
        $routes->get('/', 'ReferralController::index', ['filter' => 'auth']);
        $routes->post('/', 'ReferralController::create', ['filter' => 'auth']);
        $routes->post('use', 'ReferralController::useCode', ['filter' => 'auth']);
        $routes->get('stats', 'ReferralController::stats', ['filter' => 'auth']);
    });

    // Routes pour les feedbacks d'annonces
    $routes->group('ads', function ($routes) {
        $routes->get('(:num)/feedbacks', 'FeedbackController::listApprovedByAd/$1');
        $routes->get('(:num)/feedbacks/summary', 'FeedbackController::summaryByAd/$1');
        $routes->post('(:num)/feedbacks', 'FeedbackController::create/$1', ['filter' => 'auth']);
    });
    $routes->get('my/feedbacks', 'FeedbackController::myFeedbacks', ['filter' => 'auth']);
    $routes->delete('feedbacks/(:num)', 'FeedbackController::deleteMine/$1', ['filter' => 'auth']);

    // Routes OPTIONS pour CORS preflight - Favorites
    $routes->options('favorite/ads', 'FavoriteController::options');
    $routes->options('favorite/ads/(:any)', 'FavoriteController::options');

    // Routes pour les favoris
    $routes->group('favorite', function ($routes) {
        $routes->get('ads', 'FavoriteController::myFavorites', ['filter' => 'auth']);
        $routes->post('ads/(:num)', 'FavoriteController::toggleFavorite/$1', ['filter' => 'auth']);
        $routes->get('ads/(:num)', 'FavoriteController::checkFavorite/$1', ['filter' => 'auth']);
        $routes->delete('ads/(:num)', 'FavoriteController::removeFavorite/$1', ['filter' => 'auth']);
    });

    // Routes admin
    $routes->group('admin', ['filter' => 'admin'], function ($routes) {
        // Dashboard et statistiques
        $routes->get('dashboard', 'AdminController::dashboard');
        $routes->get('moderation-logs', 'AdminController::moderationLogs');
        $routes->get('reporting/global-stats', 'AdminReportingController::globalStats');
        $routes->get('reporting/detailed-stats', 'AdminReportingController::detailedStats');
        $routes->get('reporting/export', 'AdminReportingController::exportData');
        $routes->get('reporting/payments-stats', 'AdminReportingController::paymentsStats');
        $routes->get('reporting/payments-transactions', 'AdminReportingController::paymentsTransactions');

        // Gestion des utilisateurs
        $routes->get('users', 'AdminController::users');
        $routes->put('users/(:num)/verify-identity', 'AdminController::verifyUserIdentity/$1');
        $routes->put('users/(:num)/reject-identity', 'AdminController::rejectUserIdentity/$1');
        $routes->put('users/(:num)/identity/request-changes', 'AdminController::requestChangesUserIdentity/$1');
        $routes->put('users/(:num)/suspend', 'AdminController::suspendUser/$1');
        $routes->put('users/(:num)/unsuspend', 'AdminController::unsuspendUser/$1');
        $routes->delete('users/(:num)', 'AdminController::deleteUser/$1');

        // Gestion des annonces et modération
        $routes->get('ads', 'AdminController::allAds');
        $routes->get('ads/pending', 'AdminController::pendingAds');
        $routes->put('ads/(:num)/approve', 'AdminController::approveAd/$1');
        $routes->put('ads/(:num)/reject', 'AdminController::rejectAd/$1');

        // Gestion des signalements
        $routes->get('reports', 'AdminController::reports');
        $routes->put('reports/(:num)/resolve', 'AdminController::resolveReport/$1');

        // Gestion des référentiels
        $routes->get('referentials/categories', 'AdminReferentialController::categories');
        $routes->post('referentials/categories', 'AdminReferentialController::createCategory');
        $routes->put('referentials/categories/(:num)', 'AdminReferentialController::updateCategory/$1');
        $routes->post('referentials/categories/(:num)', 'AdminReferentialController::updateCategory/$1');
        $routes->delete('referentials/categories/(:num)', 'AdminReferentialController::deleteCategory/$1');

        $routes->get('referentials/subcategories', 'AdminReferentialController::subcategories');
        $routes->post('referentials/subcategories', 'AdminReferentialController::createSubcategory');
        $routes->put('referentials/subcategories/(:num)', 'AdminReferentialController::updateSubcategory/$1');
        $routes->post('referentials/subcategories/(:num)', 'AdminReferentialController::updateSubcategory/$1');
        $routes->delete('referentials/subcategories/(:num)', 'AdminReferentialController::deleteSubcategory/$1');

        $routes->get('referentials/filters', 'AdminReferentialController::allFilters');
        $routes->get('referentials/filters/(:num)', 'AdminReferentialController::filters/$1');
        $routes->post('referentials/filters', 'AdminReferentialController::createFilter');
        $routes->put('referentials/filters/(:num)', 'AdminReferentialController::updateFilter/$1');
        $routes->delete('referentials/filters/(:num)', 'AdminReferentialController::deleteFilter/$1');

        // Routes pour les options de filtres
        $routes->get('referentials/filter-options/(:num)', 'AdminReferentialController::getFilterOptions/$1');
        $routes->post('referentials/filter-options', 'AdminReferentialController::createFilterOption');
        $routes->put('referentials/filter-options/(:num)', 'AdminReferentialController::updateFilterOption/$1');
        $routes->delete('referentials/filter-options/(:num)', 'AdminReferentialController::deleteFilterOption/$1');

        $routes->get('referentials/brands', 'AdminReferentialController::brands');
        $routes->post('referentials/brands', 'AdminReferentialController::createBrand');
        $routes->put('referentials/brands/(:num)', 'AdminReferentialController::updateBrand/$1');
        $routes->delete('referentials/brands/(:num)', 'AdminReferentialController::deleteBrand/$1');

        // Gestion des packs promotionnels
        $routes->get('promotions/packs', 'AdminPromotionController::packs');
        $routes->post('promotions/packs', 'AdminPromotionController::createPack');
        $routes->put('promotions/packs/(:num)', 'AdminPromotionController::updatePack/$1');
        $routes->delete('promotions/packs/(:num)', 'AdminPromotionController::deletePack/$1');

        $routes->get('promotions/active', 'AdminPromotionController::activePromotions');
        $routes->post('promotions/activate', 'AdminPromotionController::activatePromotion');
        $routes->put('promotions/(:num)/deactivate', 'AdminPromotionController::deactivatePromotion/$1');
        $routes->get('promotions/stats', 'AdminPromotionController::promotionStats');

        // Admin: feedbacks
        $routes->get('feedbacks', 'AdminFeedbackController::index');
        $routes->get('feedbacks/pending', 'AdminFeedbackController::pending');
        $routes->put('feedbacks/(:num)/approve', 'AdminFeedbackController::approve/$1');
        $routes->put('feedbacks/(:num)/reject', 'AdminFeedbackController::reject/$1');
    });

    // Route pour les filtres par sous-catégorie
    $routes->get('filters/by-subcategory/(:segment)', 'FilterController::getBySubcategory/$1');

    // Route pour servir les fichiers uploadés depuis uploads/
    // Exemple: /api/uploads/ads/image.jpg
    $routes->get('uploads/(:segment)/(:any)', 'FileController::serveFile/$1/$2');
});

// Routes admin spécifiques pour la gestion des codes de parrainage
$routes->group('api/admin', ['filter' => 'admin'], function($routes) {
    $routes->get('referralcodes', 'Api\AdminReferentialController::listReferralCodes');
    $routes->get('referralcodes/(:num)/filleuls', 'Api\AdminReferentialController::referralCodeFilleuls/$1');
    $routes->post('referralcodes/(:num)/activate', 'Api\AdminReferentialController::activateReferralCode/$1');
    $routes->post('referralcodes/(:num)/deactivate', 'Api\AdminReferentialController::deactivateReferralCode/$1');
});

// Routes OPTIONS pour CORS preflight - Promotion Packs
$routes->options('api/promotion-packs', 'Api\PromotionPackController::options');
$routes->options('api/promotion-packs/(:any)', 'Api\PromotionPackController::options');

// Routes OPTIONS pour CORS preflight - Boost
$routes->options('api/boost/boost-existing-ad/(:any)', 'BoostController::options');
$routes->options('api/boost/boost-existing-ad', 'BoostController::options');
$routes->options('api/boost/check-payment/(:any)', 'BoostController::options');
$routes->options('api/boost/publish-free', 'BoostController::options');
$routes->options('api/boost/retry-payment/(:any)', 'BoostController::options');
$routes->options('api/boost/list-boosted-ads', 'BoostController::options');

// Routes OPTIONS pour CORS preflight - Feedbacks
$routes->options('api/ads/(:num)/feedbacks', 'Api\FeedbackController::options');
$routes->options('api/ads/(:num)/feedbacks/summary', 'Api\FeedbackController::options');
$routes->options('api/my/feedbacks', 'Api\FeedbackController::options');
$routes->options('api/feedbacks/(:num)', 'Api\FeedbackController::options');
$routes->options('api/admin/feedbacks', 'Api\AdminFeedbackController::options');
$routes->options('api/admin/feedbacks/pending', 'Api\AdminFeedbackController::options');
$routes->options('api/admin/feedbacks/(:num)/approve', 'Api\AdminFeedbackController::options');
$routes->options('api/admin/feedbacks/(:num)/reject', 'Api\AdminFeedbackController::options');

// Routes OPTIONS pour CORS preflight - Ads Status
$routes->options('api/ads/(:num)/status', 'Api\AdsController::options');

