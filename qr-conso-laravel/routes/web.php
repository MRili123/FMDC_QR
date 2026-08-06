<?php

use App\Http\Controllers\Admin\AssociationController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DossierController;
use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\QrLandingController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\SuiviController;
use Illuminate\Support\Facades\Route;

/*
 * Le scan d'un QR arrive sur une URL courte et sans locale : c'est elle qui est
 * imprimée en clair sous le code (§9). La page de vérification choisit ensuite
 * la langue.
 */
Route::get('/r/{code}', [QrLandingController::class, 'show'])->name('qr.landing');
Route::post('/r/{code}/signaler', [QrLandingController::class, 'report'])->name('qr.report');

Route::get('/', fn () => redirect('/'.config('app.locale')));

Route::prefix('{locale}')
    ->where(['locale' => 'fr|ar'])
    ->middleware('setlocale')
    ->group(function () {

        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/manifest.webmanifest', [ManifestController::class, 'show'])->name('manifest');

        // ---- Parcours de dépôt (§7) ----
        Route::prefix('reclamation')->name('reclamation.')->group(function () {
            Route::get('/', [ReclamationController::class, 'start'])->name('start');
            Route::get('/demarche', [ReclamationController::class, 'demarche'])->name('demarche');
            Route::post('/demarche', [ReclamationController::class, 'storeDemarche']);
            Route::get('/categorie', [ReclamationController::class, 'categorie'])->name('categorie');
            Route::post('/categorie', [ReclamationController::class, 'storeCategorie']);
            Route::get('/motif', [ReclamationController::class, 'motif'])->name('motif');
            Route::post('/motif', [ReclamationController::class, 'storeMotif']);
            Route::get('/decrire', [ReclamationController::class, 'decrire'])->name('decrire');
            Route::post('/decrire', [ReclamationController::class, 'storeDecrire']);
            Route::get('/preuves', [ReclamationController::class, 'preuves'])->name('preuves');
            Route::post('/preuves', [ReclamationController::class, 'storePreuves']);
            Route::get('/resultat', [ReclamationController::class, 'resultat'])->name('resultat');
            Route::post('/resultat', [ReclamationController::class, 'storeResultat']);
            Route::get('/contact', [ReclamationController::class, 'contact'])->name('contact');
            Route::post('/contact', [ReclamationController::class, 'submit'])->name('submit');
            Route::get('/confirmation', [ReclamationController::class, 'confirmation'])->name('confirmation');

            // Assistance IA appelée en arrière-plan depuis l'écran « Décrire ».
            Route::post('/assistance', [ReclamationController::class, 'assistance'])->name('assistance');
        });

        Route::post('/pieces', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::delete('/pieces/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

        Route::post('/otp/demander', [OtpController::class, 'request'])->name('otp.request');
        Route::post('/otp/verifier', [OtpController::class, 'verify'])->name('otp.verify');

        // ---- Suivi public ----
        Route::get('/suivi', [SuiviController::class, 'form'])->name('suivi.form');
        Route::post('/suivi', [SuiviController::class, 'lookup'])->name('suivi.lookup');
        Route::get('/suivi/{reference}', [SuiviController::class, 'show'])->name('suivi.show');

        // ---- Back-office ----
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            Route::middleware('auth')->group(function () {
                Route::get('/', [DossierController::class, 'index'])->name('dossiers.index');
                Route::get('/dossiers/{dossier}', [DossierController::class, 'show'])->name('dossiers.show');
                Route::post('/dossiers/{dossier}/statut', [DossierController::class, 'updateStatus'])->name('dossiers.status');
                Route::post('/dossiers/{dossier}/affectation', [DossierController::class, 'assign'])->name('dossiers.assign');
                Route::post('/dossiers/{dossier}/ia', [DossierController::class, 'aiSuggest'])->name('dossiers.ai');

                Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

                Route::get('/associations', [AssociationController::class, 'index'])->name('associations.index');
                Route::post('/associations', [AssociationController::class, 'store'])->name('associations.store');
                Route::post('/associations/regles', [AssociationController::class, 'storeRule'])->name('associations.rules.store');
                Route::delete('/associations/regles/{rule}', [AssociationController::class, 'destroyRule'])->name('associations.rules.destroy');

                Route::get('/qr-codes', [QrCodeController::class, 'index'])->name('qrcodes.index');
                Route::post('/qr-codes', [QrCodeController::class, 'store'])->name('qrcodes.store');
                Route::get('/qr-codes/{qrCode}/affiche', [QrCodeController::class, 'poster'])->name('qrcodes.poster');
                Route::post('/qr-codes/{qrCode}/etat', [QrCodeController::class, 'toggle'])->name('qrcodes.toggle');
            });
        });
    });
