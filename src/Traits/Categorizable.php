<?php

namespace Mostafaznv\Categories\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Categorizable
{
    /**
     * Register a saved model event with the dispatcher.
     *
     * @param \Closure|string $callback
     *
     * @return void
     */
    abstract public static function saved($callback);

    /**
     * Register a deleted model event with the dispatcher.
     *
     * @param \Closure|string $callback
     *
     * @return void
     */
    abstract public static function deleted($callback);

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $inverse
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    abstract public function morphToMany($related, $name, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $inverse = false);

    /**
     * Get all attached categories to the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(config('categories.models.category'), 'categorizable', config('categories.tables.categorizables'), 'categorizable_id', 'category_id')->withTimestamps();
    }

    /**
     * Attach the given category(ies) to the model.
     *
     * @param int|string|array|\ArrayAccess|\Mostafaznv\Categories\Models\Category $categories
     *
     * @return void
     */
    public function setCategoriesAttribute($categories): void
    {
        static::saved(function(self $model) use ($categories) {
            $model->syncCategories($categories);
        });
    }

    /**
     * Boot the categorizable trait for the model.
     *
     * @return void
     */
    public static function bootCategorizable()
    {
        static::deleted(function(self $model) {
            self::detach([], $model);
        });
    }

    /**
     * Scope query with all the given categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param mixed $categories
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAllCategories(Builder $builder, $categories): Builder
    {
        $categories = $this->prepareCategoryIds($categories);

        collect($categories)->each(function($category) use ($builder) {
            $builder->whereHas('categories', function(Builder $builder) use ($category) {
                return $builder->where('id', $category);
            });
        });

        return $builder;
    }

    /**
     * Scope query with any of the given categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param mixed $categories
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAnyCategories(Builder $builder, $categories): Builder
    {
        $categories = $this->prepareCategoryIds($categories);

        return $builder->whereHas('categories', function(Builder $builder) use ($categories) {
            $builder->whereIn('id', $categories);
        });
    }

    /**
     * Scope query with any of the given categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param mixed $categories
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCategories(Builder $builder, $categories): Builder
    {
        return static::scopeWithAnyCategories($builder, $categories);
    }

    /**
     * Scope query without any of the given categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param mixed $categories
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutCategories(Builder $builder, $categories): Builder
    {
        $categories = $this->prepareCategoryIds($categories);

        return $builder->whereDoesntHave('categories', function(Builder $builder) use ($categories) {
            $builder->whereIn('id', $categories);
        });
    }

    /**
     * Scope query without any categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutAnyCategories(Builder $builder): Builder
    {
        return $builder->doesntHave('categories');
    }

    /**
     * Determine if the model has any of the given categories.
     *
     * @param mixed $categories
     *
     * @return bool
     */
    public function hasCategories($categories): bool
    {
        $categories = $this->prepareCategoryIds($categories);

        return !$this->categories->pluck('id')->intersect($categories)->isEmpty();
    }

    /**
     * Determine if the model has any the given categories.
     *
     * @param mixed $categories
     *
     * @return bool
     */
    public function hasAnyCategories($categories): bool
    {
        return static::hasCategories($categories);
    }

    /**
     * Determine if the model has all of the given categories.
     *
     * @param mixed $categories
     *
     * @return bool
     */
    public function hasAllCategories($categories): bool
    {
        $categories = $this->prepareCategoryIds($categories);

        return collect($categories)->diff($this->categories->pluck('id'))->isEmpty();
    }

    /**
     * Sync model categories.
     *
     * @param mixed $categories
     * @param bool $detaching
     *
     * @return $this
     */
    public function syncCategories($categories, bool $detaching = true)
    {
        // Find categories
        $categories = $this->prepareCategoryIds($categories);

        // Sync model categories
        $sync = $this->categories()->sync($categories, $detaching);

        // Update stats
        self::updateStats($this, $sync);

        return $this;
    }

    /**
     * Attach model categories.
     *
     * @param mixed $categories
     *
     * @return $this
     */
    public function attachCategories($categories)
    {
        return $this->syncCategories($categories, false);
    }

    /**
     * Detach model categories.
     *
     * @param array $categories
     */
    public function detachCategories($categories = [])
    {
        self::detach($categories, $this);
    }

    /**
     * Prepare category IDs.
     *
     * @param mixed $categories
     *
     * @return array
     */
    protected function prepareCategoryIds($categories): array
    {
        // Convert collection to plain array
        if ($categories instanceof BaseCollection && is_string($categories->first()))
        {
            $categories = $categories->toArray();
        }

        // Check if categories was numeric string
        if (is_numeric($categories) || (is_array($categories) && is_numeric(array_first($categories))))
        {
            return (array)$categories;
        }

        // Find categories by slug, and get their IDs
        if (is_string($categories) || (is_array($categories) && is_string(array_first($categories))))
        {
            $categories = app('categories.category')->whereIn('slug', $categories)->get()->pluck('id');
        }

        if ($categories instanceof Model)
        {
            return [$categories->getKey()];
        }

        if ($categories instanceof Collection)
        {
            return $categories->modelKeys();
        }

        if ($categories instanceof BaseCollection)
        {
            return $categories->toArray();
        }

        return (array)$categories;
    }

    /**
     * Custom detach to handle stats.
     *
     * @param array|null $categories
     * @param $model
     * @return mixed
     */
    protected static function detach(Array $categories = null, $model)
    {
        $config = config('categories.stats');
        $categories = !empty($categories) ? $model->prepareCategoryIds($categories) : [];
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => [],
        ];

        // Check stats status
        if ($config['status'])
        {
            // Retrieve current attached categories
            $modelCategories = $model->categories();
            $keyName = $modelCategories->getModel()->getKeyName();
            $current = $modelCategories->pluck($keyName)->toArray();

            // Filter current categories which is exists in detach list
            $changes['detached'] = ($intersect = array_intersect($current, $categories)) ? $intersect : $current;
        }

        // Sync model categories
        $model->categories()->detach($categories);

        // Update Stats
        self::updateStats($model, $changes);

        return $model;
    }

    /**
     * Update stats on attach/detach
     *
     * @param $model
     * @param array $sync
     */
    protected static function updateStats($model, Array $sync)
    {
        // Check stats status
        $config = config('categories.stats');
        if (!$config['status'])
            return;

        // Init variables
        $categorizable = self::class;
        $categorizable = new $categorizable;
        $table = $categorizable->getTable();
        $categorizableTypeField = $config['categorizable_type_field'];

        if (count($sync['attached']))
        {
            // Retrieve attached categories
            $attachedCategories = app('categories.category')->whereIn('id', $sync['attached'])->get();

            self::statsOnAttach($model, $attachedCategories, $categorizableTypeField, $table);
        }
        else if (count($sync['detached']))
        {
            // Retrieve detached categories
            $detachedCategories = app('categories.category')->whereIn('id', $sync['detached'])->get();

            self::statsOnDetach($model, $detachedCategories, $categorizableTypeField, $table);
        }
    }

    /**
     * Handle stats on attach.
     *
     * @param $model
     * @param $attachedCategories
     * @param $categorizableTypeField
     * @param $table
     */
    protected static function statsOnAttach($model, $attachedCategories, $categorizableTypeField, $table)
    {
        foreach ($attachedCategories as $attachedCategory)
        {
            $stats = $attachedCategory->stats;
            $categorizableTypeValue = $model->$categorizableTypeField;

            // Check if this categorizable model has type attribute
            if (self::hasTypeAttribute($model, $categorizableTypeField))
            {
                // Init stats if category does not have any stats yet
                if (!isset($stats[$table]))
                    $stats[$table]['other'] = 0;

                // Sometimes we have type for categorizable model, but it's nullable! lets check it
                if ($model->$categorizableTypeField != null)
                {
                    // Sometimes we want to change our database logic (remove/add type from database columns), so we should convert object structure to new one
                    if (!is_array($stats[$table]))
                    {
                        $value = $stats[$table];

                        $stats[$table] = [];
                        $stats[$table]['other'] = (int)$value;
                    }

                    // Init stats for this type, or increment it
                    if (!isset($stats[$table][$categorizableTypeValue]))
                        $stats[$table][$categorizableTypeValue] = 1;
                    else
                        $stats[$table][$categorizableTypeValue] += 1;
                }
                else
                {
                    $stats[$table]['other'] += 1;
                }
            }
            else
            {
                // Init stats if category does not have any stats yet
                if (!isset($stats[$table]))
                    $stats[$table] = 1;
                else
                {
                    // Sometimes we want to change our database logic (remove/add type from database columns), so we should convert object structure to new one
                    if (is_array($stats[$table]))
                    {
                        $other = 0;
                        foreach ($stats[$table] as $value)
                            $other += $value;

                        $stats[$table] = $other;
                    }

                    $stats[$table] += 1;
                }
            }

            $attachedCategory->stats = $stats;
            $attachedCategory->save();
        }
    }

    protected static function statsOnDetach($model, $detachedCategories, $categorizableTypeField, $table)
    {
        foreach ($detachedCategories as $detachedCategory)
        {
            $stats = $detachedCategory->stats;
            $categorizableTypeValue = $model->$categorizableTypeField;

            // Check if this categorizable model has type attribute
            if (self::hasTypeAttribute($model, $categorizableTypeField))
            {
                // Init stats if category does not have any stats yet
                if (!isset($stats[$table]))
                    $stats[$table]['other'] = 0;

                // Sometimes we have type for categorizable model, but it's nullable! lets check it
                if ($model->$categorizableTypeField != null)
                {
                    // Sometimes we want to change our database logic (remove/add type from database columns), so we should convert object structure to new one
                    if (!is_array($stats[$table]))
                    {
                        $value = $stats[$table];

                        $stats[$table] = [];
                        $stats[$table]['other'] = (int)$value;
                    }

                    // Init stats for this type, or increment it
                    if (!isset($stats[$table][$categorizableTypeValue]))
                        $stats[$table][$categorizableTypeValue] = 0;
                    else
                        $stats[$table][$categorizableTypeValue] = ($stats[$table][$categorizableTypeValue] > 0) ? $stats[$table][$categorizableTypeValue] - 1 : 0;
                }
                else
                {
                    $stats[$table]['other'] = ($stats[$table]['other'] > 0) ? $stats[$table]['other'] - 1 : 0;
                }
            }
            else
            {
                // Init stats if category does not have any stats yet
                if (!isset($stats[$table]))
                    $stats[$table] = 0;
                else
                {
                    // Sometimes we want to change our database logic (remove/add type from database columns), so we should convert object structure to new one
                    if (is_array($stats[$table]))
                    {
                        $other = 0;
                        foreach ($stats[$table] as $value)
                            $other += $value;

                        $stats[$table] = $other;
                    }

                    $stats[$table] -= 1;
                }
            }

            $detachedCategory->stats = $stats;
            $detachedCategory->save();
        }
    }

    protected static function hasTypeAttribute($model, $categorizableTypeField)
    {
        $attributes = $model->toArray();

        return array_key_exists($categorizableTypeField, $attributes);
    }
}