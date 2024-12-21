<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoice;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\RecipientSendVipInvitation;
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
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['provisionElements'];

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
        return $this->belongsToMany(Contact::class)->withPivot(['type', 'note', 'order_column'])->withTimestamps();
    }

    /**
     * Get the client contact email.
     */
    protected function contactEmail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->contacts()?->orderBy('order_column')->firstWhere('type', '=', 'commercial')?->email ?? $this->contacts()?->orderBy('order_column')->firstWhere('type', '=', 'administration')?->email ?? $this->contacts()?->orderBy('order_column')->firstWhere('type', '=', 'executive')?->email ?? $this->email,
        );
    }

    /**
     * Get the client invoicing contact email.
     */
    protected function invoicingContactEmail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->contacts()?->orderBy('order_column')->firstWhere('type', '=', 'invoicing')?->email ?? $this->invoicing_email ?? $this->email,
        );
    }

    /**
     * Get the client vip contact email.
     */
    protected function vipContactEmail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->contacts()?->orderBy('order_column')->firstWhere('type', '=', 'executive')?->email ?? $this->contactEmail ?? $this->email,
        );
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
     * Get the front edit url.
     */
    protected function frontEditLink(): Attribute
    {
        return Attribute::make(
            get: fn () => URL::signedRoute('front.client', $this->id),
        );
    }

    /**
     * The current edition invoices that belong to the client.
     */
    public function currentInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->where('edition_id', setting('edition_id', config('cdn.default_edition_id')));
    }

    /**
     * The current edition provisions that belong to the client.
     */
    public function currentProvisionElements(): MorphMany
    {
        return $this->morphMany(ProvisionElement::class, 'recipient')->where('edition_id', setting('edition_id', config('cdn.default_edition_id')));
    }

    /**
     * The current edition provisions amount for the client.
     */
    public function currentProvisionElementsAmount(): float
    {
        return $this->currentProvisionElements->pluck('price.amount')->sum();
    }

    /**
     * The current edition provisions net amount for the client.
     */
    public function currentProvisionElementsNetAmount(): float
    {
        return $this->currentProvisionElements->pluck('price.net_amount')->sum();
    }

    /**
     * The current edition provisions tax amount for the client.
     */
    public function currentProvisionElementsTaxAmount(): float
    {
        return $this->currentProvisionElements->pluck('price.tax_amount')->sum();
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return array<string, string>|string
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        if ($notification instanceof ClientSendInvoice) {
            return [$this->invoicingContactEmail ?? $this->contactEmail => $this->name];
        }

        if ($notification instanceof RecipientSendVipInvitation) {
            return [$this->vipContactEmail ?? $this->contactEmail => $this->name];
        }

        return [$this->contactEmail ?? $this->email => $this->name];
    }
}
