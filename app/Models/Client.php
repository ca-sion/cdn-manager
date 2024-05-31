<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notification;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Notifiable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The provisions that belong to the client.
     */
    public function provisionElements(): MorphMany
    {
        return $this->morphMany(ProvisionElement::class, 'recipient');
    }

    /**
     * The contacts that belong to the client.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withPivot(['type', 'note'])->withTimestamps();
    }

    /**
     * The invoices that belong to the client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * The documents that belong to the client.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the category that owns the client.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ClientCategory::class, 'category_id');
    }

    /**
     * Get the pdf url.
     */
    protected function pdfLink(): Attribute
    {
        return Attribute::make(
            get: fn () => URL::signedRoute('pdf.client', $this->id),
        );
    }

    /**
     * The current edition provisions for the client.
     */
    public function currentProvisionElements(): Collection
    {
        return $this->provisionElements()->where('edition_id', setting('edition_id', config('cdn.default_edition_id')))->get();
    }

    /**
     * The current edition provisions amount for the client.
     */
    public function currentProvisionElementsAmount(): float
    {
        return $this->currentProvisionElements()->pluck('price.amount')->sum();
    }

    /**
     * The current edition provisions taxe amount for the client.
     */
    public function currentProvisionElementsTaxeAmount(): float
    {
        return $this->currentProvisionElements()->pluck('price.tax_amount')->sum();
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return  array<string, string>|string
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        return [$this->email ?? $this->invoicing_email => $this->name];
    }
}
