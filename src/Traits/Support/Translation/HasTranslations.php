<?php

namespace Mostafaznv\Categories\Traits\Support\Translation;

use Illuminate\Support\Str;
use Spatie\Translatable\Events\TranslationHasBeenSet;
use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if (!$this->isTranslatableAttribute($key))
        {
            return parent::getAttributeValue($key);
        }

        return $this->getTranslation($key, config('app.locale')) ?: ($this->getTranslations( $key )[ 0 ] ?? null);
    }

    /**
     * Get translations.
     *
     * @param $key
     * @return array
     * @throws \Spatie\Translatable\Exceptions\AttributeIsNotTranslatable
     */
    public function getTranslations($key): array
    {
        $this->guardAgainstNonTranslatableAttribute($key);
        $value = json_decode($this->getAttributes()[$key] ?? '' ?: '{}', true);

        // Inject default translation if none supplied
        if (!is_array($value))
        {
            $oldValue = $value;

            if ($this->hasSetMutator($key))
            {
                $method = 'set' . Str::studly($key) . 'Attribute';
                $value = $this->{$method}($value);
            }

            $value = [$locale = app()->getLocale() => $value];

            $this->attributes[$key] = $this->asJson($value);
            event(new TranslationHasBeenSet($this, $key, $locale, $oldValue, $value));
        }

        return $value;
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $values = array_map(function($attribute) {
            return $this->getTranslation($attribute, config('app.locale')) ?: null;
        }, $keys = $this->getTranslatableAttributes());

        return array_replace(parent::attributesToArray(), array_combine($keys, $values));
    }
}