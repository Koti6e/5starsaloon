<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $services = Service::query()
            ->with(['category', 'images'])
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->boolean('packages'), fn ($query) => $query->where('is_package', true))
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
            'categories' => ServiceCategory::query()->orderBy('display_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', [
            'service' => new Service(['price_type' => 'fixed', 'currency_code' => 'INR', 'status' => 'active']),
            'categories' => ServiceCategory::query()->orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['service_code'] = $data['service_code'] ?: (($data['is_package'] ?? false) ? 'PKG-' : 'SVC-').Str::upper(Str::slug($data['name'], '-'));

        DB::transaction(function () use ($request, $data): void {
            $service = Service::query()->create($data);
            $this->storeGalleryImages($request, $service);
            $this->normalizeCover($service->fresh('images'));
        });

        return redirect()->route('admin.services.index')->with('status', 'Service created.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', [
            'service' => $service->load('images'),
            'categories' => ServiceCategory::query()->orderBy('display_order')->get(),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $this->validated($request, $service);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['service_code'] = $data['service_code'] ?: $service->service_code;

        DB::transaction(function () use ($request, $service, $data): void {
            $service->update($data);
            $service = $service->fresh('images');
            $this->updateGalleryMetadata($request, $service);
            $this->storeGalleryImages($request, $service);
            $this->normalizeCover($service->fresh('images'));
        });

        return redirect()->route('admin.services.edit', $service)->with('status', 'Service updated.');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['status' => $service->status === 'active' ? 'inactive' : 'active']);

        return back()->with('status', 'Service status updated.');
    }

    public function toggleFavorite(Service $service): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $service->update(['is_featured' => ! $service->is_featured]);

        return response()->json([
            'id' => $service->id,
            'is_favourite' => $service->is_featured,
        ]);
    }

    public function destroyImage(Service $service, ServiceImage $image): RedirectResponse
    {
        abort_unless($image->service_id === $service->id, 404);

        $paths = [$image->image_path, $image->thumbnail_path];

        DB::transaction(function () use ($service, $image): void {
            $image->delete();
            $this->normalizeCover($service->fresh('images'));
        });

        $this->deleteFiles($paths);

        return back()->with('status', 'Service image deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($service?->id)],
            'service_code' => ['nullable', 'string', 'max:255', Rule::unique('services', 'service_code')->ignore($service?->id)],
            'short_description' => ['required', 'string', 'max:1000'],
            'detailed_description' => ['nullable', 'string'],
            'price_type' => ['required', Rule::in(['fixed', 'starting_from', 'range', 'contact'])],
            'price' => ['nullable', 'numeric', 'min:0'],
            'minimum_price' => ['nullable', 'numeric', 'min:0'],
            'maximum_price' => ['nullable', 'numeric', 'min:0', 'gte:minimum_price'],
            'discounted_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'image' => ['nullable', 'string', 'max:255'],
            'service_images' => ['nullable', 'array', 'max:4'],
            'service_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'cover_image_id' => ['nullable', 'integer'],
            'image_sort_order' => ['nullable', 'array'],
            'image_sort_order.*' => ['integer', 'min:0', 'max:4'],
            'image_alt' => ['nullable', 'array'],
            'image_alt.*' => ['nullable', 'string', 'max:255'],
            'pricing_note' => ['nullable', 'string', 'max:1000'],
            'included_services' => ['nullable', 'string', 'max:2000'],
            'regular_total' => ['nullable', 'numeric', 'min:0'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_featured' => ['nullable', 'boolean'],
            'is_package' => ['nullable', 'boolean'],
            'is_salon_service_available' => ['nullable', 'boolean'],
            'is_home_service_available' => ['nullable', 'boolean'],
            'home_service_price' => ['nullable', 'numeric', 'min:0'],
            'home_service_visit_charge' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['currency_code'] = 'INR';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_package'] = $request->boolean('is_package');
        $data['is_salon_service_available'] = $request->boolean('is_salon_service_available', true);
        $data['is_home_service_available'] = $request->boolean('is_home_service_available');
        $data['price_on_request'] = $data['price_type'] === 'contact';
        $data['included_services'] = filled($data['included_services'] ?? null)
            ? collect(explode("\n", (string) $data['included_services']))->map(fn ($item) => trim($item))->filter()->values()->all()
            : null;

        if ($data['price_type'] === 'fixed' && ! filled($data['price'] ?? null)) {
            throw ValidationException::withMessages(['price' => 'A fixed-price service requires a price.']);
        }

        if ($data['price_type'] === 'starting_from' && ! filled($data['minimum_price'] ?? $data['price'] ?? null)) {
            throw ValidationException::withMessages(['minimum_price' => 'A starting-from service requires a starting price.']);
        }

        if ($data['price_type'] === 'range' && (! filled($data['minimum_price'] ?? null) || ! filled($data['maximum_price'] ?? null))) {
            throw ValidationException::withMessages(['minimum_price' => 'A range-price service requires minimum and maximum prices.']);
        }

        if ($data['price_type'] === 'contact') {
            $data['price'] = null;
            $data['minimum_price'] = null;
            $data['maximum_price'] = null;
            $data['discounted_price'] = null;
        } elseif ($data['price_type'] === 'starting_from') {
            $data['minimum_price'] = $data['minimum_price'] ?? $data['price'];
            $data['price'] = $data['minimum_price'];
            $data['maximum_price'] = null;
        } elseif ($data['price_type'] === 'range') {
            $data['price'] = null;
            $data['discounted_price'] = null;
        } else {
            $data['minimum_price'] = null;
            $data['maximum_price'] = null;
        }

        if (($data['regular_total'] ?? null) && ($data['price'] ?? null) && $data['regular_total'] > $data['price']) {
            $data['savings_amount'] = $data['regular_total'] - $data['price'];
        } else {
            $data['savings_amount'] = null;
        }

        unset($data['service_images'], $data['image_alt_text'], $data['cover_image_id'], $data['image_sort_order'], $data['image_alt']);

        return $data;
    }

    private function updateGalleryMetadata(Request $request, Service $service): void
    {
        foreach ($service->images as $image) {
            $image->update([
                'alt_text' => $request->input("image_alt.{$image->id}", $image->alt_text),
                'sort_order' => (int) $request->input("image_sort_order.{$image->id}", $image->sort_order),
                'is_cover' => (int) $request->input('cover_image_id') === $image->id,
            ]);
        }
    }

    private function storeGalleryImages(Request $request, Service $service): void
    {
        $uploads = $request->file('service_images', []);
        if ($uploads === []) {
            return;
        }

        $existingCount = $service->images()->count();
        if ($existingCount + count($uploads) > 4) {
            throw ValidationException::withMessages([
                'service_images' => 'Each service can have a maximum of four images total.',
            ]);
        }

        File::ensureDirectoryExists(public_path('images/services'));

        foreach ($uploads as $index => $file) {
            $baseName = Str::slug($service->slug.'-'.pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::random(8);
            $sourcePath = public_path("images/services/{$baseName}.{$file->extension()}");
            $imagePath = "images/services/{$baseName}.webp";
            $thumbnailPath = "images/services/{$baseName}-thumb.webp";

            $file->move(dirname($sourcePath), basename($sourcePath));
            $this->writeWebp($sourcePath, public_path($imagePath), 1200, 900);
            $this->writeWebp($sourcePath, public_path($thumbnailPath), 360, 270);
            @unlink($sourcePath);

            $service->images()->create([
                'image_path' => $imagePath,
                'thumbnail_path' => $thumbnailPath,
                'alt_text' => $request->string('image_alt_text')->trim()->toString() ?: $service->name.' service image',
                'is_cover' => $existingCount === 0 && $index === 0,
                'sort_order' => $existingCount + $index + 1,
            ]);
        }
    }

    private function normalizeCover(Service $service): void
    {
        $images = $service->images()->orderBy('sort_order')->get();
        if ($images->isEmpty()) {
            return;
        }

        $cover = $images->firstWhere('is_cover', true) ?: $images->first();
        $service->images()->whereKeyNot($cover->id)->update(['is_cover' => false]);
        $cover->update(['is_cover' => true]);
    }

    private function writeWebp(string $source, string $destination, int $maxWidth, int $maxHeight): void
    {
        [$width, $height, $type] = getimagesize($source);
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $targetWidth = (int) round($width * $ratio);
        $targetHeight = (int) round($height * $ratio);
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            default => null,
        };

        if (! $image) {
            copy($source, $destination);

            return;
        }

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagewebp($canvas, $destination, 84);
        imagedestroy($image);
        imagedestroy($canvas);
    }

    /**
     * @param  array<int, string|null>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && str_starts_with($path, 'images/services/')) {
                File::delete(public_path($path));
            }
        }
    }
}
