<?php

namespace Mostafaznv\Categories\Models;

use Exception;
use Kalnoy\Nestedset\NestedSet;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\Model;
use Mostafaznv\Categories\Builders\EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Mostafaznv\Categories\Traits\Support\Cache\CacheableEloquent;
use Mostafaznv\Categories\Traits\Support\Slug\Sluggable;
use Mostafaznv\Categories\Traits\Support\Translation\HasTranslations;
use Mostafaznv\Categories\Traits\Support\Validation\ValidatingTrait;

/**
 * Mostafaznv\Categories\Models\Category.
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array $description
 * @property int $_lft
 * @property int $_rgt
 * @property int $parent_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Kalnoy\Nestedset\Collection|\Mostafaznv\Categories\Models\Category[] $children
 * @property-read \Mostafaznv\Categories\Models\Category|null $parent
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereLft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereRgt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mostafaznv\Categories\Models\Category whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Category extends Model
{
    use NodeTrait, HasTranslations, ValidatingTrait, CacheableEloquent, Sluggable;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'stats',
        NestedSet::LFT,
        NestedSet::RGT,
        NestedSet::PARENT_ID,
    ];

    protected $casts = [
        'slug'               => 'string',
        NestedSet::LFT       => 'integer',
        NestedSet::RGT       => 'integer',
        NestedSet::PARENT_ID => 'integer',
        'deleted_at'         => 'datetime',
    ];

    protected $sluggable = [
        'field'     => 'slug',
        'from'      => 'name',
        'on_create' => true,
        'on_update' => true,
        'separator' => '-',
        'lang'      => null
    ];


    protected $observables = [
        'validating',
        'validated',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = [
        'name',
        'description',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Whether the model should throw a
     * ValidationException if it fails validation.
     *
     * @var bool
     */
    protected $throwValidationExceptions = true;

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('categories.tables.categories'));
        $this->setRules($this->setRules());
    }

    /**
     * Set default rules and merge them with custom rules
     *
     * @return array
     */
    protected function setRules()
    {
        $rules = [
            'name'               => 'required|string|max:150',
            'description'        => 'nullable|string|max:10000',
            'slug'               => 'required|alpha_dash|max:150|unique:' . config('categories.tables.categories') . ',slug',
            'hex'                => 'nullable|string|max:7',
            'stats'              => 'nullable|string|max:10000',
            NestedSet::LFT       => 'sometimes|required|integer',
            NestedSet::RGT       => 'sometimes|required|integer',
            NestedSet::PARENT_ID => 'nullable|integer',
        ];

        if (count(config('categories.rules')))
        {
            $extraRules = config('categories.rules');
            $rules = array_merge($rules, $extraRules);
        }

        return $rules;
    }

    /**
     * Get all attached models of the given class to the category.
     *
     * @param string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function entries(string $class): MorphToMany
    {
        return $this->morphedByMany($class, 'categorizable', config('categories.tables.categorizables'), 'category_id', 'categorizable_id');
    }

    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }

    /**
     * Cast Stats to json on retrieve
     *
     * @param $value
     * @return mixed
     */
    public function getStatsAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Cast stats to json string on save data
     *
     * @param $value
     */
    public function setStatsAttribute($value)
    {
        $this->attributes['stats'] = json_encode($value);
    }

    /**
     * Set custom property
     *
     * @param $name
     * @param array $casts
     */
    public function setCustomProperty($name, Array $casts)
    {
        $validNames = ['casts', 'rules', 'sluggable'];

        if (in_array($name, $validNames) and isset($this->$name))
        {
            foreach ($casts as $key => $cast)
                $this->$name[$key] = $cast;
        }
    }

    /**
     * Render Html Select
     *
     * @param string $return array or collection
     * @param array $prepend to prepend an item (example: placeholder)
     * @param null $type
     *
     * @return array|static
     * @throws Exception
     */
    public static function htmlSelect($return = 'array', Array $prepend = [], $type = null)
    {
        if (!in_array($return, ['array', 'collection']))
            throw new Exception("return value should be 'array' or 'collection'");

        if (count($prepend) and !isset($prepend[1]))
            throw new Exception("prepend is not a valid key-value array");

        $options = [];

        if ($type)
            $tree = app('categories.category')->where('type', $type)->get()->toTree()->toArray();
        else
            $tree = app('categories.category')->get()->toTree()->toArray();

        $options = self::htmlOption($tree, $options, 0, '');
        $options = collect($options);

        $keys = $options->pluck('id');
        $values = $options->pluck('label');


        $options = $keys->combine($values);

        if (isset($prepend[0]))
            $options->prepend($prepend[0], $prepend[1]);

        if ($return == 'array')
            return $options->toArray();
        else if ($return == 'collection')
            return $options;

        return [];
    }

    /**
     * Generate options, recursive function
     *
     * @param $tree
     * @param $options
     * @param int $depth
     * @param string $label
     * @return array
     */
    protected static function htmlOption($tree, $options, $depth = 0, $label = '')
    {
        $separator = config('categories.html.select.separator');

        foreach ($tree as $key => $value)
        {
            if ($depth == 0)
                $current_label = $value['name'];
            else
                $current_label = $label . $separator . $value['name'];

            $options[] = [
                'id'    => $value['id'],
                'label' => $current_label,
            ];


            if (!empty($value['children']))
            {
                if ($depth == 0)
                    $new_label = $value['name'];
                else
                    $new_label = $label . $separator . $value['name'];

                $options = self::htmlOption($value['children'], $options, $depth + 1, $new_label);
            }
        }

        return $options;
    }
}