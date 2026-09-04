<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] class extends Component
{
    use WithFileUploads;

    public string $businessName = '';

    public $logo = null;
    public ?string $logoPath = null;

    public string $primaryColor = '#FFFFFF';

    public int $logoZoom = 100;
    public int $logoOffsetX = 0;
    public int $logoOffsetY = 0;

    public $heroImage = null;
    public ?string $heroImagePath = null;
    public int $heroImageZoom = 100;
    public int $heroImagePositionX = 50;
    public int $heroImagePositionY = 50;

    public string $heroEyebrow = '';
    public string $heroTitle = '';
    public string $heroHighlight = '';
    public string $heroDescription = '';

    public string $bookingButtonLabel = '';

    public string $servicesTitle = '';
    public string $servicesDescription = '';

    public string $phone = '';
    public string $email = '';
    public string $notificationEmail = '';
    public string $address = '';

    public bool $saved = false;


    /*
    |--------------------------------------------------------------------------
    | Initialisation
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $settings = SiteSetting::firstOrCreate(
            [],
            [
                'business_name' => 'Votre établissement',

                'primary_color' => '#FFFFFF',

                'logo_zoom' => 100,
                'logo_offset_x' => 0,
                'logo_offset_y' => 0,

                'hero_image_zoom' => 100,
                'hero_image_position_x' => 50,
                'hero_image_position_y' => 50,

                'hero_eyebrow' => 'Bienvenue',
                'hero_title' => 'Prenez rendez-vous',
                'hero_highlight' => 'simplement.',
                'hero_description' => 'Découvrez nos prestations, choisissez ce qui vous convient et réservez votre créneau en quelques instants.',

                'booking_button_label' => 'Prendre rendez-vous',

                'services_title' => 'Choisissez votre prestation',
                'services_description' => 'Retrouvez les prestations actuellement disponibles à la réservation.',
            ]
        );


        $this->businessName = $settings->business_name ?? '';

        $this->logoPath = $settings->logo_path;

        $this->primaryColor =
            $settings->primary_color ?? '#FFFFFF';

        $this->logoZoom =
            (int) ($settings->logo_zoom ?? 100);

        $this->logoOffsetX =
            (int) ($settings->logo_offset_x ?? 0);

        $this->logoOffsetY =
            (int) ($settings->logo_offset_y ?? 0);


        $this->heroImagePath =
            $settings->hero_image_path;

        $this->heroImageZoom =
            (int) ($settings->hero_image_zoom ?? 100);

        $this->heroImagePositionX =
            (int) ($settings->hero_image_position_x ?? 50);

        $this->heroImagePositionY =
            (int) ($settings->hero_image_position_y ?? 50);


        $this->heroEyebrow =
            $settings->hero_eyebrow ?? '';

        $this->heroTitle =
            $settings->hero_title ?? '';

        $this->heroHighlight =
            $settings->hero_highlight ?? '';

        $this->heroDescription =
            $settings->hero_description ?? '';


        $this->bookingButtonLabel =
            $settings->booking_button_label ?? '';


        $this->servicesTitle =
            $settings->services_title ?? '';

        $this->servicesDescription =
            $settings->services_description ?? '';


        $this->phone =
            $settings->phone ?? '';

        $this->email =
            $settings->email ?? '';

        $this->notificationEmail =
            $settings->notification_email ?? '';

        $this->address =
            $settings->address ?? '';
    }


    /*
    |--------------------------------------------------------------------------
    | Nouveau logo sélectionné
    |--------------------------------------------------------------------------
    */

    private function generateEmailLogo(
    string $logoPath,
    int $zoom,
    int $offsetX,
    int $offsetY
): void {
    $disk = Storage::disk('public');

    $sourcePath = $disk->path($logoPath);

    if (! file_exists($sourcePath)) {
        return;
    }

    /*
     * Le cadre utilisé sur le site fait 180 × 48 px.
     *
     * On génère l'image en x2 pour qu'elle reste nette
     * sur les écrans Retina / mobiles.
     */
    $pixelRatio = 2;

    $frameWidth = 180 * $pixelRatio;
    $frameHeight = 48 * $pixelRatio;

    $translatedX = $offsetX * $pixelRatio;
    $translatedY = $offsetY * $pixelRatio;

    $image = new \Imagick($sourcePath);

    /*
     * Si jamais un GIF/WebP animé est fourni,
     * on ne garde que la première image.
     */
    if ($image->getNumberImages() > 1) {
        $image->setIteratorIndex(0);
    }

    if (method_exists($image, 'autoOrientImage')) {
        $image->autoOrientImage();
    }

    $sourceWidth = $image->getImageWidth();
    $sourceHeight = $image->getImageHeight();

    if ($sourceWidth <= 0 || $sourceHeight <= 0) {
        $image->clear();
        $image->destroy();

        return;
    }

    /*
     * Reproduit object-contain du header.
     */
    $containScale = min(
        $frameWidth / $sourceWidth,
        $frameHeight / $sourceHeight
    );

    $zoomScale = max(1, $zoom / 100);

    $renderedWidth = max(
        1,
        (int) round(
            $sourceWidth
            * $containScale
            * $zoomScale
        )
    );

    $renderedHeight = max(
        1,
        (int) round(
            $sourceHeight
            * $containScale
            * $zoomScale
        )
    );

    $image->resizeImage(
        $renderedWidth,
        $renderedHeight,
        \Imagick::FILTER_LANCZOS,
        1
    );

    /*
     * Canvas final transparent.
     */
    $canvas = new \Imagick();

    $canvas->newImage(
        $frameWidth,
        $frameHeight,
        new \ImagickPixel('transparent'),
        'png'
    );

    /*
     * L'image est centrée comme avec object-contain,
     * puis on applique les déplacements enregistrés.
     */
    $x = (int) round(
        (($frameWidth - $renderedWidth) / 2)
        + $translatedX
    );

    $y = (int) round(
        (($frameHeight - $renderedHeight) / 2)
        + $translatedY
    );

    $canvas->compositeImage(
        $image,
        \Imagick::COMPOSITE_OVER,
        $x,
        $y
    );

    $canvas->setImageFormat('png');

    $disk->makeDirectory('branding');

    $canvas->writeImage(
        $disk->path('branding/logo-email.png')
    );

    $image->clear();
    $image->destroy();

    $canvas->clear();
    $canvas->destroy();
}

    public function updatedLogo(): void
    {
        /*
         * À chaque nouveau logo, on repart d'un cadrage neutre.
         */

        $this->logoZoom = 100;
        $this->logoOffsetX = 0;
        $this->logoOffsetY = 0;

        $this->saved = false;
    }


    /*
    |--------------------------------------------------------------------------
    | Nouvelle image hero sélectionnée
    |--------------------------------------------------------------------------
    */

    public function updatedHeroImage(): void
    {
        $this->heroImageZoom = 100;
        $this->heroImagePositionX = 50;
        $this->heroImagePositionY = 50;

        $this->saved = false;
    }


    /*
    |--------------------------------------------------------------------------
    | Sauvegarde
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $validated = $this->validate([

            'businessName' => [
                'required',
                'string',
                'max:255',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],

            'primaryColor' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'logoZoom' => [
                'required',
                'integer',
                'min:100',
                'max:300',
            ],

            'logoOffsetX' => [
                'required',
                'integer',
            ],

            'logoOffsetY' => [
                'required',
                'integer',
            ],

            'heroImage' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:5120',
            ],

            'heroImageZoom' => [
                'required',
                'integer',
                'min:100',
                'max:300',
            ],

            'heroImagePositionX' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],

            'heroImagePositionY' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],

            'heroEyebrow' => [
                'nullable',
                'string',
                'max:255',
            ],

            'heroTitle' => [
                'required',
                'string',
                'max:255',
            ],

            'heroHighlight' => [
                'nullable',
                'string',
                'max:255',
            ],

            'heroDescription' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'bookingButtonLabel' => [
                'required',
                'string',
                'max:255',
            ],

            'servicesTitle' => [
                'required',
                'string',
                'max:255',
            ],

            'servicesDescription' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'notificationEmail' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

        ]);


        $settings = SiteSetting::firstOrCreate([]);


        /*
        |--------------------------------------------------------------------------
        | Nouveau logo
        |--------------------------------------------------------------------------
        */

        if ($this->logo) {

            if ($settings->logo_path) {

                Storage::disk('public')->delete(
                    $settings->logo_path
                );

            }


            $this->logoPath = $this->logo->store(
                'branding',
                'public'
            );

        }

        if ($this->logoPath) {
            $this->generateEmailLogo(
                $this->logoPath,
                $validated['logoZoom'],
                $validated['logoOffsetX'],
                $validated['logoOffsetY'],
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Nouvelle image du hero
        |--------------------------------------------------------------------------
        */

        if ($this->heroImage) {

            if ($settings->hero_image_path) {

                Storage::disk('public')->delete(
                    $settings->hero_image_path
                );

            }


            $this->heroImagePath = $this->heroImage->store(
                'hero',
                'public'
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Mise à jour
        |--------------------------------------------------------------------------
        */

        $settings->update([

            'business_name' =>
                $validated['businessName'],

            'logo_path' =>
                $this->logoPath,

            'primary_color' =>
                strtoupper(
                    $validated['primaryColor']
                ),

            'logo_zoom' =>
                $validated['logoZoom'],

            'logo_offset_x' =>
                $validated['logoOffsetX'],

            'logo_offset_y' =>
                $validated['logoOffsetY'],


            'hero_image_path' =>
                $this->heroImagePath,

            'hero_image_zoom' =>
                $validated['heroImageZoom'],

            'hero_image_position_x' =>
                $validated['heroImagePositionX'],

            'hero_image_position_y' =>
                $validated['heroImagePositionY'],


            'hero_eyebrow' =>
                $validated['heroEyebrow'] ?: null,

            'hero_title' =>
                $validated['heroTitle'],

            'hero_highlight' =>
                $validated['heroHighlight'] ?: null,

            'hero_description' =>
                $validated['heroDescription'] ?: null,


            'booking_button_label' =>
                $validated['bookingButtonLabel'],


            'services_title' =>
                $validated['servicesTitle'],

            'services_description' =>
                $validated['servicesDescription'] ?: null,


            'phone' =>
                $validated['phone'] ?: null,

            'email' =>
                $validated['email'] ?: null,

            'notification_email' =>
                $validated['notificationEmail'] ?: null,

            'address' =>
                $validated['address'] ?: null,

        ]);


        /*
         * Le fichier temporaire Livewire n'est plus nécessaire
         * une fois le logo enregistré.
         */

        $this->logo = null;
        $this->heroImage = null;

        $this->saved = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Supprimer le logo
    |--------------------------------------------------------------------------
    */

    public function removeLogo(): void
    {
        $settings = SiteSetting::first();


        if (! $settings) {
            return;
        }


        if ($settings->logo_path) {

            Storage::disk('public')->delete(
                $settings->logo_path
            );

            Storage::disk('public')->delete(
                'branding/logo-email.png'
            );

        }

    


        /*
         * On supprime également le cadrage associé.
         */

        $settings->update([

            'logo_path' => null,

            'logo_zoom' => 100,
            'logo_offset_x' => 0,
            'logo_offset_y' => 0,

        ]);


        $this->logo = null;
        $this->logoPath = null;

        $this->logoZoom = 100;
        $this->logoOffsetX = 0;
        $this->logoOffsetY = 0;

        $this->saved = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Supprimer l'image du hero
    |--------------------------------------------------------------------------
    */

    public function removeHeroImage(): void
    {
        $settings = SiteSetting::first();

        if (! $settings) {
            return;
        }

        if ($settings->hero_image_path) {
            Storage::disk('public')->delete(
                $settings->hero_image_path
            );
        }

        $settings->update([
            'hero_image_path' => null,
            'hero_image_zoom' => 100,
            'hero_image_position_x' => 50,
            'hero_image_position_y' => 50,
        ]);

        $this->heroImage = null;
        $this->heroImagePath = null;
        $this->heroImageZoom = 100;
        $this->heroImagePositionX = 50;
        $this->heroImagePositionY = 50;

        $this->saved = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Modification du formulaire
    |--------------------------------------------------------------------------
    */

    public function updated(): void
    {
        $this->saved = false;
    }
};

?>


<div class="p-6 lg:p-10">

    <div class="mx-auto max-w-7xl">


        {{-- En-tête --}}

        <div>

            <p class="text-sm font-medium uppercase tracking-widest text-zinc-500">
                Dashboard
            </p>

            <h1 class="mt-2 text-3xl font-semibold">
                Personnalisation
            </h1>

            <p class="mt-2 max-w-2xl text-zinc-500">
                Personnalisez l’identité et les informations visibles sur votre site public.
            </p>

        </div>



        <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1fr)_420px] xl:items-start">

        <form
            wire:submit="save"
            class="order-2 space-y-6 xl:order-1"
        >


            {{-- Identité visuelle --}}

            <section class="rounded-2xl border border-zinc-700 p-6">

                <h2 class="text-xl font-semibold">
                    Identité visuelle
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Ajoutez votre logo et choisissez la couleur principale de votre site.
                </p>



                {{-- Logo --}}

<div class="mt-7">

    <label class="text-sm font-medium">
        Logo
    </label>

    <p class="mt-1 text-sm text-zinc-500">
        Déplacez directement l’image dans le cadre pour ajuster son affichage dans le header.
    </p>


    @if ($logo || $logoPath)

        <div
            class="mt-5"
            x-data="{
                zoom: @js($logoZoom),
                x: @js($logoOffsetX),
                y: @js($logoOffsetY),

                dragging: false,
                startX: 0,
                startY: 0,
                originX: 0,
                originY: 0,

                startDrag(event) {
                    this.dragging = true;

                    this.startX = event.clientX;
                    this.startY = event.clientY;

                    this.originX = this.x;
                    this.originY = this.y;

                    event.currentTarget.setPointerCapture?.(event.pointerId);
                },

                drag(event) {
                    if (! this.dragging) return;

                    this.x =
                        this.originX +
                        (event.clientX - this.startX);

                    this.y =
                        this.originY +
                        (event.clientY - this.startY);
                },

                stopDrag() {
                    if (! this.dragging) return;

                    this.dragging = false;

                    this.x = Math.round(this.x);
                    this.y = Math.round(this.y);

                    $wire.$set('logoOffsetX', this.x, false);
                    $wire.$set('logoOffsetY', this.y, true);
                },

                changeZoom(value) {
                    this.zoom = Math.max(
                        100,
                        Math.min(300, Number(value))
                    );

                    $wire.$set(
                        'logoZoom',
                        Math.round(this.zoom),
                        false
                    );
                },

                commitZoom() {
                    $wire.$set(
                        'logoZoom',
                        Math.round(this.zoom),
                        true
                    );
                },

                wheelZoom(event) {
                    const direction =
                        event.deltaY < 0 ? 5 : -5;

                    this.changeZoom(
                        this.zoom + direction
                    );

                    this.commitZoom();
                },

                resetCrop() {
                    this.zoom = 100;
                    this.x = 0;
                    this.y = 0;

                    $wire.$set('logoZoom', 100, false);
                    $wire.$set('logoOffsetX', 0, false);
                    $wire.$set('logoOffsetY', 0, true);
                }
            }"
        >


            {{-- Simulation du header --}}

            <div class="rounded-2xl border border-zinc-700 bg-zinc-950 p-6">

                <p class="mb-4 text-xs font-medium uppercase tracking-wider text-zinc-600">
                    Aperçu dans le header
                </p>


                {{-- Zone réellement visible --}}

                <div
                    class="relative cursor-grab overflow-hidden border border-zinc-800 bg-zinc-950 active:cursor-grabbing"
                    style="
                        width: 180px;
                        height: 48px;
                        touch-action: none;
                    "
                    @pointerdown.prevent="startDrag($event)"
                    @pointermove.window="drag($event)"
                    @pointerup.window="stopDrag()"
                    @pointercancel.window="stopDrag()"
                    @wheel.prevent="wheelZoom($event)"
                >

                    @if ($logo)

                        <img
                            src="{{ $logo->temporaryUrl() }}"
                            alt="Aperçu du logo"
                            draggable="false"
                            class="pointer-events-none absolute inset-0 h-full w-full select-none object-contain"
                            :style="
                                `transform:
                                    translate(${x}px, ${y}px)
                                    scale(${zoom / 100});
                                 transform-origin: center;`
                            "
                        >

                    @elseif ($logoPath)

                        <img
                            src="{{ asset('storage/' . $logoPath) }}"
                            alt="Logo actuel"
                            draggable="false"
                            class="pointer-events-none absolute inset-0 h-full w-full select-none object-contain"
                            :style="
                                `transform:
                                    translate(${x}px, ${y}px)
                                    scale(${zoom / 100});
                                 transform-origin: center;`
                            "
                        >

                    @endif

                </div>


                <p class="mt-4 text-xs text-zinc-500">
                    Cliquez et glissez l’image pour la repositionner.
                    Sur ordinateur, vous pouvez aussi utiliser la molette pour zoomer.
                </p>

            </div>


            {{-- Zoom --}}

            <div class="mt-5">

                <div class="flex items-center justify-between">

                    <label class="text-sm font-medium">
                        Zoom
                    </label>

                    <span
                        class="text-sm text-zinc-500"
                        x-text="Math.round(zoom) + ' %'"
                    ></span>

                </div>

                <input
                    type="range"
                    min="100"
                    max="300"
                    step="1"
                    :value="zoom"
                    @input="changeZoom($event.target.value)"
                    @change="commitZoom()"
                    class="mt-3 w-full cursor-pointer"
                >

            </div>


            {{-- Actions cadrage --}}

            <div class="mt-4">

                <button
                    type="button"
                    @click="resetCrop()"
                    class="text-sm text-zinc-400 underline underline-offset-4 transition hover:text-white"
                >
                    Réinitialiser le cadrage
                </button>

            </div>

        </div>

    @else

        <div class="mt-4 flex h-28 w-48 items-center justify-center rounded-xl border border-dashed border-zinc-700 bg-zinc-900">

            <span class="text-sm text-zinc-600">
                Aucun logo
            </span>

        </div>

    @endif



    {{-- Upload --}}

    <div class="mt-5">

        <label class="inline-flex cursor-pointer items-center rounded-xl border border-zinc-700 px-4 py-2.5 text-sm font-medium transition hover:bg-zinc-800">

            {{ $logo || $logoPath
                ? 'Choisir une autre image'
                : 'Choisir une image'
            }}

            <input
                type="file"
                wire:model="logo"
                accept="image/png,image/jpeg,image/webp"
                class="hidden"
            >

        </label>


        <div
            wire:loading
            wire:target="logo"
            class="mt-2 text-xs text-zinc-500"
        >
            Chargement de l’image...
        </div>


        <p class="mt-2 text-xs text-zinc-500">
            PNG, JPG ou WebP · 2 Mo maximum
        </p>


        @if ($logoPath)

            <button
                type="button"
                wire:click="removeLogo"
                class="mt-3 block text-sm text-red-400 transition hover:text-red-300"
            >
                Supprimer le logo
            </button>

        @endif


        @error('logo')

            <p class="mt-2 text-sm text-red-400">
                {{ $message }}
            </p>

        @enderror

    </div>

</div>



                {{-- Couleur principale --}}

                <div class="mt-8 border-t border-zinc-700 pt-6">

                    <label class="text-sm font-medium">
                        Couleur principale
                    </label>

                    <p class="mt-1 text-sm text-zinc-500">
                        Cette couleur sera utilisée pour les principaux éléments visuels du site.
                    </p>


                    <div class="mt-4 flex flex-wrap items-center gap-4">

                        <input
                            type="color"
                            wire:model.live="primaryColor"
                            class="h-12 w-16 cursor-pointer rounded-lg border border-zinc-700 bg-transparent p-1"
                        >


                        <input
                            type="text"
                            wire:model.live="primaryColor"
                            maxlength="7"
                            class="w-32 rounded-xl border border-zinc-700 bg-transparent px-4 py-3 font-mono text-sm uppercase outline-none focus:border-zinc-500"
                        >


                        <div
                            class="h-10 w-10 rounded-full border border-zinc-700"
                            style="background-color: {{ $primaryColor }};"
                        ></div>

                    </div>


                    @error('primaryColor')

                        <p class="mt-2 text-sm text-red-400">
                            Utilisez une couleur au format #FFFFFF.
                        </p>

                    @enderror

                </div>

            </section>



            {{-- Identité --}}

            <section class="rounded-2xl border border-zinc-700 p-6">

                <h2 class="text-xl font-semibold">
                    Identité
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Nom affiché sur le site.
                </p>


                <div class="mt-6">

                    <label class="text-sm font-medium">
                        Nom de l’établissement
                    </label>

                    <input
                        type="text"
                        wire:model.live.debounce.250ms="businessName"
                        class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                    >

                    @error('businessName')

                        <p class="mt-2 text-sm text-red-400">
                            {{ $message }}
                        </p>

                    @enderror

                </div>

            </section>



            {{-- Hero --}}

            <section class="rounded-2xl border border-zinc-700 p-6">

                <h2 class="text-xl font-semibold">
                    En-tête de la page
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Personnalisez le message principal de votre page d’accueil.
                </p>


                {{-- Image du hero --}}

                <div class="mt-7 border-b border-zinc-700 pb-7">

                    <label class="text-sm font-medium">
                        Image de couverture
                    </label>

                    <p class="mt-1 text-sm text-zinc-500">
                        Ajoutez une image puis déplacez-la directement dans le cadre pour choisir le cadrage visible sur le site.
                    </p>


                    @if ($heroImage || $heroImagePath)

                        <div
                            class="mt-5"
                            x-data="{
                                zoom: @js($heroImageZoom),
                                x: @js($heroImagePositionX),
                                y: @js($heroImagePositionY),

                                dragging: false,
                                startX: 0,
                                startY: 0,
                                originX: 50,
                                originY: 50,
                                frameWidth: 1,
                                frameHeight: 1,

                                clamp(value) {
                                    return Math.max(0, Math.min(100, value));
                                },

                                startDrag(event) {
                                    this.dragging = true;
                                    this.startX = event.clientX;
                                    this.startY = event.clientY;
                                    this.originX = this.x;
                                    this.originY = this.y;

                                    const rect = event.currentTarget.getBoundingClientRect();
                                    this.frameWidth = rect.width || 1;
                                    this.frameHeight = rect.height || 1;

                                    event.currentTarget.setPointerCapture?.(event.pointerId);
                                },

                                drag(event) {
                                    if (! this.dragging) return;

                                    const deltaX =
                                        ((event.clientX - this.startX) / this.frameWidth) * 100;

                                    const deltaY =
                                        ((event.clientY - this.startY) / this.frameHeight) * 100;

                                    this.x = this.clamp(this.originX - deltaX);
                                    this.y = this.clamp(this.originY - deltaY);
                                },

                                stopDrag() {
                                    if (! this.dragging) return;

                                    this.dragging = false;
                                    this.x = Math.round(this.x);
                                    this.y = Math.round(this.y);

                                    $wire.$set('heroImagePositionX', this.x, false);
                                    $wire.$set('heroImagePositionY', this.y, true);
                                },

                                changeZoom(value) {
                                    this.zoom = Math.max(
                                        100,
                                        Math.min(300, Number(value))
                                    );

                                    $wire.$set(
                                        'heroImageZoom',
                                        Math.round(this.zoom),
                                        false
                                    );
                                },

                                commitZoom() {
                                    $wire.$set(
                                        'heroImageZoom',
                                        Math.round(this.zoom),
                                        true
                                    );
                                },

                                wheelZoom(event) {
                                    const direction =
                                        event.deltaY < 0 ? 5 : -5;

                                    this.changeZoom(
                                        this.zoom + direction
                                    );

                                    this.commitZoom();
                                },

                                resetCrop() {
                                    this.zoom = 100;
                                    this.x = 50;
                                    this.y = 50;

                                    $wire.$set('heroImageZoom', 100, false);
                                    $wire.$set('heroImagePositionX', 50, false);
                                    $wire.$set('heroImagePositionY', 50, true);
                                }
                            }"
                        >

                            <div class="overflow-hidden rounded-2xl border border-zinc-700 bg-zinc-950">

                                <div class="border-b border-zinc-800 px-5 py-3">

                                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-600">
                                        Aperçu du hero
                                    </p>

                                </div>


                                <div
                                    class="relative aspect-[16/6] cursor-grab overflow-hidden bg-zinc-900 active:cursor-grabbing"
                                    style="touch-action: none;"
                                    @pointerdown.prevent="startDrag($event)"
                                    @pointermove.window="drag($event)"
                                    @pointerup.window="stopDrag()"
                                    @pointercancel.window="stopDrag()"
                                    @wheel.prevent="wheelZoom($event)"
                                >

                                    @if ($heroImage)

                                        <img
                                            src="{{ $heroImage->temporaryUrl() }}"
                                            alt="Aperçu de l’image du hero"
                                            draggable="false"
                                            class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover"
                                            :style="
                                                `object-position: ${x}% ${y}%;
                                                 transform: scale(${zoom / 100});
                                                 transform-origin: ${x}% ${y}%;`
                                            "
                                        >

                                    @elseif ($heroImagePath)

                                        <img
                                            src="{{ asset('storage/' . $heroImagePath) }}"
                                            alt="Image actuelle du hero"
                                            draggable="false"
                                            class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover"
                                            :style="
                                                `object-position: ${x}% ${y}%;
                                                 transform: scale(${zoom / 100});
                                                 transform-origin: ${x}% ${y}%;`
                                            "
                                        >

                                    @endif


                                    <div class="pointer-events-none absolute inset-0 bg-black/20"></div>

                                    <div class="pointer-events-none absolute inset-x-0 bottom-0 p-5">

                                        <p class="text-xs text-white/70">
                                            Glissez l’image pour choisir la zone visible.
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="mt-5">

                                <div class="flex items-center justify-between gap-4">

                                    <label class="text-sm font-medium">
                                        Zoom
                                    </label>

                                    <span
                                        class="text-sm text-zinc-500"
                                        x-text="Math.round(zoom) + ' %'"
                                    ></span>

                                </div>

                                <input
                                    type="range"
                                    min="100"
                                    max="300"
                                    step="1"
                                    :value="zoom"
                                    @input="changeZoom($event.target.value)"
                                    @change="commitZoom()"
                                    class="mt-3 w-full cursor-pointer"
                                >

                            </div>


                            <button
                                type="button"
                                @click="resetCrop()"
                                class="mt-4 text-sm text-zinc-400 underline underline-offset-4 transition hover:text-white"
                            >
                                Réinitialiser le cadrage
                            </button>

                        </div>

                    @else

                        <div class="mt-4 flex aspect-[16/6] max-w-2xl items-center justify-center rounded-2xl border border-dashed border-zinc-700 bg-zinc-900">

                            <span class="text-sm text-zinc-600">
                                Aucune image de couverture
                            </span>

                        </div>

                    @endif


                    <div class="mt-5">

                        <label class="inline-flex cursor-pointer items-center rounded-xl border border-zinc-700 px-4 py-2.5 text-sm font-medium transition hover:bg-zinc-800">

                            {{ $heroImage || $heroImagePath
                                ? 'Choisir une autre image'
                                : 'Choisir une image'
                            }}

                            <input
                                type="file"
                                wire:model="heroImage"
                                accept="image/png,image/jpeg,image/webp"
                                class="hidden"
                            >

                        </label>


                        <div
                            wire:loading
                            wire:target="heroImage"
                            class="mt-2 text-xs text-zinc-500"
                        >
                            Chargement de l’image...
                        </div>


                        <p class="mt-2 text-xs text-zinc-500">
                            PNG, JPG ou WebP · 5 Mo maximum
                        </p>


                        @if ($heroImagePath)

                            <button
                                type="button"
                                wire:click="removeHeroImage"
                                class="mt-3 block text-sm text-red-400 transition hover:text-red-300"
                            >
                                Supprimer l’image
                            </button>

                        @endif


                        @error('heroImage')

                            <p class="mt-2 text-sm text-red-400">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>

                </div>


                <div class="mt-6 grid gap-5 md:grid-cols-2">


                    <div>

                        <label class="text-sm font-medium">
                            Petit titre
                        </label>

                        <input
                            type="text"
                            wire:model.live.debounce.250ms="heroEyebrow"
                            placeholder="Bienvenue"
                            class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                        >

                    </div>


                    <div>

                        <label class="text-sm font-medium">
                            Texte du bouton
                        </label>

                        <input
                            type="text"
                            wire:model.live.debounce.250ms="bookingButtonLabel"
                            placeholder="Prendre rendez-vous"
                            class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                        >

                    </div>


                    <div>

                        <label class="text-sm font-medium">
                            Titre principal
                        </label>

                        <input
                            type="text"
                            wire:model.live.debounce.250ms="heroTitle"
                            placeholder="Prenez rendez-vous"
                            class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                        >

                    </div>


                    <div>

                        <label class="text-sm font-medium">
                            Texte mis en avant
                        </label>

                        <input
                            type="text"
                            wire:model.live.debounce.250ms="heroHighlight"
                            placeholder="simplement."
                            class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                        >

                    </div>

                </div>


                <div class="mt-5">

                    <label class="text-sm font-medium">
                        Description
                    </label>

                    <textarea
                        wire:model.live.debounce.250ms="heroDescription"
                        rows="4"
                        class="mt-2 w-full resize-none rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                    ></textarea>

                </div>

            </section>



            {{-- Prestations --}}

            <section class="rounded-2xl border border-zinc-700 p-6">

                <h2 class="text-xl font-semibold">
                    Section prestations
                </h2>


                <div class="mt-6">

                    <label class="text-sm font-medium">
                        Titre
                    </label>

                    <input
                        type="text"
                        wire:model.live.debounce.250ms="servicesTitle"
                        class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                    >

                </div>


                <div class="mt-5">

                    <label class="text-sm font-medium">
                        Description
                    </label>

                    <textarea
                        wire:model.live.debounce.250ms="servicesDescription"
                        rows="3"
                        class="mt-2 w-full resize-none rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                    ></textarea>

                </div>

            </section>



            {{-- Coordonnées --}}

            <section class="rounded-2xl border border-zinc-700 p-6">

                <h2 class="text-xl font-semibold">
                    Coordonnées
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Informations de contact de l’établissement.
                </p>


                <div class="mt-6 grid gap-5 md:grid-cols-2">


                    <div>

                        <label class="text-sm font-medium">
                            Téléphone
                        </label>

                        <input
                            type="text"
                            wire:model.live.debounce.250ms="phone"
                            placeholder="06 00 00 00 00"
                            class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                        >

                    </div>


                    <div>

                        <label class="text-sm font-medium">
                            Email
                        </label>

                        <input
                            type="email"
                            wire:model.live.debounce.250ms="email"
                            placeholder="contact@exemple.fr"
                            class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                        >

                    </div>

                    <div>
    <label class="text-sm font-medium">
        Email de notification
    </label>

    <p class="mt-1 text-xs text-zinc-500">
        Adresse qui recevra les notifications de nouvelles réservations.
        Elle n’est pas affichée publiquement.
    </p>

    <input
        type="email"
        wire:model.live.debounce.250ms="notificationEmail"
        placeholder="reservations@exemple.fr"
        class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
    >

    @error('notificationEmail')
        <p class="mt-2 text-sm text-red-400">
            {{ $message }}
        </p>
    @enderror
</div>

                </div>


                <div class="mt-5">

                    <label class="text-sm font-medium">
                        Adresse
                    </label>

                    <input
                        type="text"
                        wire:model.live.debounce.250ms="address"
                        placeholder="1 rue Exemple, 34000 Montpellier"
                        class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none focus:border-zinc-500"
                    >

                </div>

            </section>



            {{-- Sauvegarde --}}

            <div class="flex items-center justify-end gap-4">

                @if ($saved)

                    <span class="text-sm text-green-400">
                        Modifications enregistrées ✓
                    </span>

                @endif


                <button
                    type="submit"
                    class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200"
                >
                    Enregistrer
                </button>

            </div>


        </form>


        {{-- Aperçu en direct --}}

        <aside class="order-1 xl:sticky xl:top-6 xl:order-2">

            @php
                $previewPrimaryColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $primaryColor)
                    ? strtoupper($primaryColor)
                    : '#FFFFFF';

                $previewHex = ltrim($previewPrimaryColor, '#');
                $previewR = hexdec(substr($previewHex, 0, 2));
                $previewG = hexdec(substr($previewHex, 2, 2));
                $previewB = hexdec(substr($previewHex, 4, 2));
                $previewLuminance = (0.2126 * $previewR + 0.7152 * $previewG + 0.0722 * $previewB) / 255;
                $previewButtonText = $previewLuminance > 0.6 ? '#18181b' : '#ffffff';
            @endphp

            <div class="overflow-hidden rounded-2xl border border-zinc-700 bg-zinc-900/40">

                <div class="flex items-center justify-between gap-4 border-b border-zinc-700 px-5 py-4">

                    <div>
                        <p class="font-semibold">
                            Aperçu en direct
                        </p>

                        <p class="mt-1 text-xs text-zinc-500">
                            Pas besoin d’enregistrer pour vérifier vos changements.
                        </p>
                    </div>

                    <a
                        href="{{ url('/') }}"
                        target="_blank"
                        class="shrink-0 text-xs font-medium text-zinc-400 underline underline-offset-4 transition hover:text-white"
                    >
                        Ouvrir le site ↗
                    </a>

                </div>


                <div class="p-4">

                    <div class="overflow-hidden rounded-xl border border-zinc-800 bg-black text-white shadow-2xl">

                        {{-- Mini header --}}

                        <div class="flex min-h-16 items-center justify-between gap-3 border-b border-white/10 px-4 py-2">

                            <div class="min-w-0">

                                @if ($logo)

                                    <div class="relative overflow-hidden" style="width: 180px; height: 48px; max-width: 190px;">
                                        <img
                                            src="{{ $logo->temporaryUrl() }}"
                                            alt="Aperçu du logo"
                                            class="absolute inset-0 h-full w-full object-contain"
                                            style="transform: translate({{ $logoOffsetX }}px, {{ $logoOffsetY }}px) scale({{ $logoZoom / 100 }}); transform-origin: center;"
                                        >
                                    </div>

                                @elseif ($logoPath)

                                    <div class="relative overflow-hidden" style="width: 180px; height: 48px; max-width: 190px;">
                                        <img
                                            src="{{ asset('storage/' . $logoPath) }}"
                                            alt="{{ $businessName }}"
                                            class="absolute inset-0 h-full w-full object-contain"
                                            style="transform: translate({{ $logoOffsetX }}px, {{ $logoOffsetY }}px) scale({{ $logoZoom / 100 }}); transform-origin: center;"
                                        >
                                    </div>

                                @else

                                    <p class="truncate text-sm font-semibold">
                                        {{ $businessName ?: 'Votre établissement' }}
                                    </p>

                                @endif

                            </div>

                            <span
                                class="shrink-0 rounded-lg px-3 py-2 text-[10px] font-semibold"
                                style="background-color: {{ $previewPrimaryColor }}; color: {{ $previewButtonText }};"
                            >
                                {{ $bookingButtonLabel ?: 'Prendre rendez-vous' }}
                            </span>

                        </div>


                        {{-- Mini hero --}}

                        <div class="relative min-h-[260px] overflow-hidden">

                            @if ($heroImage)

                                <img
                                    src="{{ $heroImage->temporaryUrl() }}"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover"
                                    style="object-position: {{ $heroImagePositionX }}% {{ $heroImagePositionY }}%; transform: scale({{ $heroImageZoom / 100 }}); transform-origin: {{ $heroImagePositionX }}% {{ $heroImagePositionY }}%;"
                                >

                            @elseif ($heroImagePath)

                                <img
                                    src="{{ asset('storage/' . $heroImagePath) }}"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover"
                                    style="object-position: {{ $heroImagePositionX }}% {{ $heroImagePositionY }}%; transform: scale({{ $heroImageZoom / 100 }}); transform-origin: {{ $heroImagePositionX }}% {{ $heroImagePositionY }}%;"
                                >

                            @endif

                            @if ($heroImage || $heroImagePath)
                                <div
                                    class="absolute inset-0"
                                    style="background: linear-gradient(90deg, rgba(9,9,11,.94) 0%, rgba(9,9,11,.72) 55%, rgba(9,9,11,.35) 100%);"
                                ></div>
                            @endif

                            <div class="relative z-10 p-5">

                                @if ($heroEyebrow)
                                    <p class="text-[9px] font-medium uppercase tracking-[0.18em] text-zinc-400">
                                        {{ $heroEyebrow }}
                                    </p>
                                @endif

                                <h3 class="mt-3 text-2xl font-semibold leading-tight tracking-tight">
                                    <span class="block">
                                        {{ $heroTitle ?: 'Prenez rendez-vous' }}
                                    </span>

                                    @if ($heroHighlight)
                                        <span
                                            class="block"
                                            style="color: {{ $previewPrimaryColor }};"
                                        >
                                            {{ $heroHighlight }}
                                        </span>
                                    @endif
                                </h3>

                                @if ($heroDescription)
                                    <p class="mt-3 max-w-[300px] text-xs leading-relaxed text-zinc-300">
                                        {{ $heroDescription }}
                                    </p>
                                @endif

                                <div class="mt-5 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-lg px-3 py-2 text-[10px] font-semibold"
                                        style="background-color: {{ $previewPrimaryColor }}; color: {{ $previewButtonText }};"
                                    >
                                        {{ $bookingButtonLabel ?: 'Prendre rendez-vous' }}
                                    </span>

                                    <span class="rounded-lg border border-white/20 bg-black/20 px-3 py-2 text-[10px] font-medium text-zinc-200">
                                        Voir les prestations
                                    </span>
                                </div>

                            </div>

                        </div>


                        {{-- Mini prestations --}}

                        <div class="border-t border-white/10 px-5 py-5">
                            <p class="text-[9px] font-medium uppercase tracking-[0.18em] text-zinc-500">
                                Prestations
                            </p>

                            <p class="mt-2 text-base font-semibold">
                                {{ $servicesTitle ?: 'Choisissez votre prestation' }}
                            </p>

                            @if ($servicesDescription)
                                <p class="mt-2 text-[11px] leading-relaxed text-zinc-500">
                                    {{ $servicesDescription }}
                                </p>
                            @endif
                        </div>


                        {{-- Mini footer --}}

                        <div class="border-t border-white/10 px-5 py-4 text-[10px] text-zinc-500">
                            <p class="font-medium text-zinc-300">
                                {{ $businessName ?: 'Votre établissement' }}
                            </p>

                            @if ($phone || $email || $address)
                                <p class="mt-1 line-clamp-2">
                                    {{ collect([$address, $phone, $email])->filter()->implode(' · ') }}
                                </p>
                            @endif
                        </div>

                    </div>

                </div>

            </div>

        </aside>

        </div>

    </div>

</div>