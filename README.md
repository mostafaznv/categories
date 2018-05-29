# Categories

**Categories** is a polymorphic Laravel package, for category management. You can categorize any eloquent model with ease, and utilize the power of **[Nested Sets](https://github.com/lazychaser/laravel-nestedset)**, and the awesomeness of **[Translatable](https://github.com/spatie/laravel-translatable)** models out of the box.

This project is a fork of [Rinvex Categories](https://github.com/rinvex/categories) with some modifications and some extra features

## Installation

1. Install the package via composer:
    ```shell
    composer require mostafaznv/categories
    ```

2. Publish migrations and config:
    ```
    php artisan vendor:publish --provider="Mostafaznv\Categories\CategoriesServiceProvider"
    ```

3. Execute migrations via the following command:
    ```
    php artisan migrate
    ```

4. Done!


## Usage

To add categories support to your eloquent models simply use `\Mostafaznv\Categories\Traits\Categorizable` trait.

### Manage your categories

Your categories are just normal [eloquent](https://laravel.com/docs/master/eloquent) models, so you can deal with it like so. Nothing special here!

> **Notes:** Since **Categories** extends and utilizes other awesome packages, checkout the following documentations for further details:
> - Powerful Nested Sets using [`kalnoy/nestedset`](https://github.com/lazychaser/laravel-nestedset)
> - Translatable out of the box using [`spatie/laravel-translatable`](https://github.com/spatie/laravel-translatable)

### Customize base category model

You can create a model in `app` directory and extend our main category model to set some modifications.

First: create a model
```php
<?php

namespace App;

use Mostafaznv\Categories\Models\Category as BaseCategory;

class Category extends BaseCategory
{
    protected $hidden = ['deleted_at'];   
}
```

Then: modify `config/categories.php`

```php
<?php

return [
    //...

    'models' => [
        'category' => \App\Category::class,
    ],

    //...
];
```

### Set and edit model properties
sometimes you want to add some key-values to an exist property in base model (like `casts` and `rules`, `sluggable`). you can do it with `setCustomProperty`
```php
<?php

namespace App;

use Mostafaznv\Categories\Models\Category as BaseCategory;

class Category extends BaseCategory
{
    protected $customCasts = ['id' => 'string', '_lft' => 'string', 'attribute' => 'type'];
    protected $customSluggable = ['lang' => 'fa'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setCustomProperty('casts', $this->customCasts);
        $this->setCustomProperty('sluggable', $this->customSluggable);
    }
}
```

### Slug Configuration
with power of `setCustomProperty`, you can change some behaviours in sluggable trait

sluggable will use this configuration options to generate a unique slug
```bash

protected $sluggable = [
    'field' => 'slug',
    'from' => 'name',
    'on_create' => true,
    'on_update' => true,
    'separator' => '-',
    'lang' => null
];

```


### Manage your categorizable model

The API is intutive and very straightfarwad, so let's give it a quick look:

```php
// Get instance of your model
$post = new \App\Post::find();

// Get attached categories collection
$post->categories;

// Get attached categories query builder
$post->categories();
```

You can attach categories in various ways:

```php
// Single category id
$post->attachCategories(1);

// Multiple category IDs array
$post->attachCategories([1, 2, 5]);

// Multiple category IDs (numeric strings) array
$post->attachCategories(["1", "2", "5"]);

// Multiple category IDs collection
$post->attachCategories(collect([1, 2, 5]));

// Single category model instance
$categoryInstance = app('categories.category')->first();
$post->attachCategories($categoryInstance);

// Single category slug
$post->attachCategories('test-category');

// Multiple category slugs array
$post->attachCategories(['first-category', 'second-category']);

// Multiple category slugs collection
$post->attachCategories(collect(['first-category', 'second-category']));

// Multiple category model instances
$categoryInstances = app('categories.category')->whereIn('id', [1, 2, 5])->get();
$post->attachCategories($categoryInstances);
```

> **Notes:** 
> - In this version, attachCategories() does not support `uuid` IDs, just integer (int and numeric strings)`.
> - The `attachCategories()` method attach the given categories to the model without touching the currently attached categories, while there's the `syncCategories()` method that can detach any records that's not in the given items, this method takes a second optional boolean parameter that's set detaching flag to `true` or `false`.
> - To detach model categories you can use the `detachCategories()` method, which uses **exactly** the same signature as the `attachCategories()` method, with additional feature of detaching all currently attached categories by passing null or nothing to that method as follows: `$post->detachCategories();`.

And as you may have expected, you can check if categories attached:

```php
// Single category id
$post->hasAnyCategories(1);

// Multiple category IDs array
$post->hasAnyCategories([1, 2, 5]);

// Multiple category IDs collection
$post->hasAnyCategories(collect([1, 2, 5]));

// Single category model instance
$categoryInstance = app('categories.category')->first();
$post->hasAnyCategories($categoryInstance);

// Single category slug
$post->hasAnyCategories('test-category');

// Multiple category slugs array
$post->hasAnyCategories(['first-category', 'second-category']);

// Multiple category slugs collection
$post->hasAnyCategories(collect(['first-category', 'second-category']));

// Multiple category model instances
$categoryInstances = app('categories.category')->whereIn('id', [1, 2, 5])->get();
$post->hasAnyCategories($categoryInstances);
```

> **Notes:** 
> - The `hasAnyCategories()` method check if **ANY** of the given categories are attached to the model. It returns boolean `true` or `false` as a result.
> - Similarly the `hasAllCategories()` method uses **exactly** the same signature as the `hasAnyCategories()` method, but it behaves differently and performs a strict comparison to check if **ALL** of the given categories are attached.

### Advanced usage

#### Generate category slugs

**Categories** auto generates slugs and auto detect and insert default translation for you if not provided, but you still can pass it explicitly through normal eloquent `create` method, as follows:

```php
app('categories.category')->create(['name' => ['en' => 'My New Category'], 'slug' => 'custom-category-slug']);
```

#### Smart parameter detection

**Categories** methods that accept list of categories are smart enough to handle almost all kinds of inputs as you've seen in the above examples. It will check input type and behave accordingly. 

#### Retrieve all models attached to the category

You may encounter a situation where you need to get all models attached to certain category, you do so with ease as follows:

```php
$category = app('categories.category')->find(1);
$category->entries(\App\Models\Post::class);
```

#### Query scopes

Yes, **Categories** shipped with few awesome query scopes for your convenience, usage example:

```php
// Single category id
$post->withAnyCategories(1)->get();

// Multiple category IDs array
$post->withAnyCategories([1, 2, 5])->get();

// Multiple category IDs collection
$post->withAnyCategories(collect([1, 2, 5]))->get();

// Single category model instance
$categoryInstance = app('categories.category')->first();
$post->withAnyCategories($categoryInstance)->get();

// Single category slug
$post->withAnyCategories('test-category')->get();

// Multiple category slugs array
$post->withAnyCategories(['first-category', 'second-category'])->get();

// Multiple category slugs collection
$post->withAnyCategories(collect(['first-category', 'second-category']))->get();

// Multiple category model instances
$categoryInstances = app('categories.category')->whereIn('id', [1, 2, 5])->get();
$post->withAnyCategories($categoryInstances)->get();
```

> **Notes:**
> - The `withAnyCategories()` scope finds posts with **ANY** attached categories of the given. It returns normally a query builder, so you can chain it or call `get()` method for example to execute and get results.
> - Similarly there's few other scopes like `withAllCategories()` that finds posts with **ALL** attached categories of the given, `withoutCategories()` which finds posts without **ANY** attached categories of the given, and lastly `withoutAnyCategories()` which find posts without **ANY** attached categories at all. All scopes are created equal, with same signature, and returns query builder.

#### Category translations

Manage category translations with ease as follows:

```php
$category = app('categories.category')->find(1);

// Update name translations
$category->setTranslation('name', 'en', 'New English Category Name')->save();

// Alternatively you can use default eloquent update
$category->update([
    'name' => [
        'en' => 'New Category',
        'fa' => 'دسته بندی جدید',
    ],
]);

// Get single category translation
$category->getTranslation('name', 'en');

// Get all category translations
$category->getTranslations('name');

// Get category name in default locale
$category->name;
```

> **Note:** Check **[Translatable](https://github.com/spatie/laravel-translatable)** package for further details.

___

## Manage your nodes/nestedsets

- [Inserting Categories](#inserting-categories)
    - [Creating categories](#creating-categories)
    - [Making a root from existing category](#making-a-root-from-existing-category)
    - [Appending and prepending to the specified parent](#appending-and-prepending-to-the-specified-parent)
    - [Create a category with a specific type](#Create-a-category-with-a-specific-type)
    - [Inserting before or after specified category](#inserting-before-or-after-specified-category)
    - [Building a tree from array](#building-a-tree-from-array)
    - [Rebuilding a tree from array](#rebuilding-a-tree-from-array)
- [Retrieving categories](#retrieving-categories)
    - [Ancestors](#ancestors)
    - [Descendants](#descendants)
    - [Siblings](#siblings)
    - [Getting related models from other table](#getting-related-models-from-other-table)
    - [Including category depth](#including-category-depth)
    - [Default order](#default-order)
        - [Shifting a category](#shifting-a-category)
    - [Constraints](#constraints)
    - [Building a tree](#building-a-tree)
        - [Building flat tree](#building-flat-tree)
        - [Getting a subtree](#getting-a-subtree)
    - [Render HTML dropdown menu](#render-html-dropdown-menu)
- [Deleting categories](#deleting-categories)
- [Helper methods](#helper-methods)
- [Checking consistency](#checking-consistency)
    - [Fixing tree](#fixing-tree)

### Inserting categories

Moving and inserting categories includes several database queries, so **transaction is automatically started**
when category is saved. It is safe to use global transaction if you work with several models.

Another important note is that **structural manipulations are deferred** until you hit `save` on model
(some methods implicitly call `save` and return boolean result of the operation).

If model is successfully saved it doesn't mean that category was moved. If your application
depends on whether the category has actually changed its position, use `hasMoved` method:

```php
if ($category->save()) {
    $moved = $category->hasMoved();
}
```

#### Creating categories

When you simply create a category, it will be appended to the end of the tree:

```php
app('categories.category')->createByName('Additional Category'); // Saved as root

app('categories.category')->create($attributes); // Saved as root

$category = app('categories.category')->fill($attributes);
$category->save(); // Saved as root
```

In this case the category is considered a _root_ which means that it doesn't have a parent.

#### Making a root from existing category

The category will be appended to the end of the tree:

```php
// #1 Implicit save
$category->saveAsRoot();

// #2 Explicit save
$category->makeRoot()->save();
```

#### Appending and prepending to the specified parent

If you want to make category a child of other category, you can make it last or first child.
Suppose that `$parent` is some existing category, there are few ways to append a category:

```php
// #1 Using deferred insert
$category->appendToNode($parent)->save();

// #2 Using parent category
$parent->appendNode($category);

// #3 Using parent's children relationship
$parent->children()->create($attributes);

// #5 Using category's parent relationship
$category->parent()->associate($parent)->save();

// #6 Using the parent attribute
$category->parent_id = $parent->getKey();
$category->save();

// #7 Using static method
app('categories.category')->create($attributes, $parent);
```

And only a couple ways to prepend:

```php
// #1 Using deferred insert
$category->prependToNode($parent)->save();

// #2 Using parent category
$parent->prependNode($category);
```


#### Create a category with a specific type
type is a nullable tinyInteger attribute. 

you can create categories with specific types and use it in your queries

```php

$category->type = Category::ARTICLE_TYPE; // Category::ARTICLE_TYPE = 1 or 2 or ... 
$category->save();

```

#### Inserting before or after specified category

You can make `$category` to be a neighbor of the `$neighbor` category.
Suppose that `$neighbor` is some existing category, while target category can be fresh.
If target category exists, it will be moved to the new position and parent will be changed if it's required.

```php
# Explicit save
$category->afterNode($neighbor)->save();
$category->beforeNode($neighbor)->save();

# Implicit save
$category->insertAfterNode($neighbor);
$category->insertBeforeNode($neighbor);
```

#### Building a tree from array

When using static method `create` on category, it checks whether attributes contains `children` key.
If it does, it creates more categories recursively, as follows:

```php
$category = app('categories.category')->create([
    'name' => [
        'en' => 'New Category Name',
    ],

    'children' => [
        [
            'name' => 'Bar',

            'children' => [
                [ 'name' => 'Baz' ],
            ],
        ],
    ],
]);
```

`$category->children` now contains a list of created child categories.

#### Rebuilding a tree from array

You can easily rebuild a tree. This is useful for mass-changing the structure of the tree.
Given the `$data` as an array of categories, you can build the tree as follows:

```php
$data = [
    [ 'id' => 1, 'name' => 'foo', 'children' => [ ... ] ],
    [ 'name' => 'bar' ],
];

app('categories.category')->rebuildTree($data, $delete);
```

There is an id specified for category with the name of `foo` which means that existing
category will be filled and saved. If category does not exists `ModelNotFoundException` is
thrown. Also, this category has `children` specified which is also an array of categories;
they will be processed in the same manner and saved as children of category `foo`.

Category `bar` has no primary key specified, so it will treated as a new one, and be created.

`$delete` shows whether to delete categories that are already exists but not present
in `$data`. By default, categories aren't deleted.

### Retrieving categories

_In some cases we will use an `$id` variable which is an id of the target category._

#### Ancestors

Ancestors make a chain of parents to the category.
Helpful for displaying breadcrumbs to the current category.

```php
// #1 Using accessor
$result = $category->getAncestors();

// #2 Using a query
$result = $category->ancestors()->get();

// #3 Getting ancestors by primary key
$result = app('categories.category')->ancestorsOf($id);
```

#### Descendants

Descendants are all categories in a sub tree,
i.e. children of category, children of children, etc.

```php
// #1 Using relationship
$result = $category->descendants;

// #2 Using a query
$result = $category->descendants()->get();

// #3 Getting descendants by primary key
$result = app('categories.category')->descendantsOf($id);

// #3 Get descendants and the category by id
$result = app('categories.category')->descendantsAndSelf($id);
```

Descendants can be eagerly loaded:

```php
$categories = app('categories.category')->with('descendants')->whereIn('id', $idList)->get();
```

#### Siblings

Siblings are categories that have same parent.

```php
$result = $category->getSiblings();

$result = $category->siblings()->get();
```

To get only next siblings:

```php
// Get a sibling that is immediately after the category
$result = $category->getNextSibling();

// Get all siblings that are after the category
$result = $category->getNextSiblings();

// Get all siblings using a query
$result = $category->nextSiblings()->get();
```

To get previous siblings:

```php
// Get a sibling that is immediately before the category
$result = $category->getPrevSibling();

// Get all siblings that are before the category
$result = $category->getPrevSiblings();

// Get all siblings using a query
$result = $category->prevSiblings()->get();
```

#### Getting related models from other table

Imagine that each category `has many` products. I.e. `HasMany` relationship is established.
How can you get all products of `$category` and every its descendant? Easy!

```php
// Get ids of descendants
$categories = $category->descendants()->pluck('id');

// Include the id of category itself
$categories[] = $category->getKey();

// Get products
$goods = Product::whereIn('category_id', $categories)->get();
```

Now imagine that each category `has many` posts. I.e. `morphToMany` relationship is established this time.
How can you get all posts of `$category` and every its descendant? Is that even possible?! Sure!

```php
// Get ids of descendants
$categories = $category->descendants()->pluck('id');

// Include the id of category itself
$categories[] = $category->getKey();

// Get posts
$posts = \App\Models\Post::withCategories($categories)->get();
```

#### Including category depth

If you need to know at which level the category is:

```php
$result = app('categories.category')->withDepth()->find($id);

$depth = $result->depth;
```

Root category will be at level 0. Children of root categories will have a level of 1, etc.
To get categories of specified level, you can apply `having` constraint:

```php
$result = app('categories.category')->withDepth()->having('depth', '=', 1)->get();
```

#### Default order

Each category has it's own unique `_lft` value that determines its position in the tree.
If you want category to be ordered by this value, you can use `defaultOrder` method
on the query builder:

```php
// All categories will now be ordered by lft value
$result = app('categories.category')->defaultOrder()->get();
```

You can get categories in reversed order:

```php
$result = app('categories.category')->reversed()->get();
```

##### Shifting a category

To shift category up or down inside parent to affect default order:

```php
$bool = $category->down();
$bool = $category->up();

// Shift category by 3 siblings
$bool = $category->down(3);
```

The result of the operation is boolean value of whether the category has changed its position.

#### Constraints

Various constraints that can be applied to the query builder:

- **whereIsRoot()** to get only root categories;
- **whereIsAfter($id)** to get every category (not just siblings) that are after a category with specified id;
- **whereIsBefore($id)** to get every category that is before a category with specified id.

Descendants constraints:

```php
$result = app('categories.category')->whereDescendantOf($category)->get();
$result = app('categories.category')->whereNotDescendantOf($category)->get();
$result = app('categories.category')->orWhereDescendantOf($category)->get();
$result = app('categories.category')->orWhereNotDescendantOf($category)->get();

// Include target category into result set
$result = app('categories.category')->whereDescendantOrSelf($category)->get();
```

Ancestor constraints:

```php
$result = app('categories.category')->whereAncestorOf($category)->get();
```

`$category` can be either a primary key of the model or model instance.

#### Building a tree

After getting a set of categories, you can convert it to tree. For example:

```php
$tree = app('categories.category')->get()->toTree();
```

This will fill `parent` and `children` relationships on every category in the set and
you can render a tree using recursive algorithm:

```php
$categories = app('categories.category')->get()->toTree();

$traverse = function ($categories, $prefix = '-') use (&$traverse) {
    foreach ($categories as $category) {
        echo PHP_EOL.$prefix.' '.$category->name;

        $traverse($category->children, $prefix.'-');
    }
};

$traverse($categories);
```

This will output something like this:

```
- Root
-- Child 1
--- Sub child 1
-- Child 2
- Another root
```

##### Building flat tree

Also, you can build a flat tree: a list of categories where child categories are immediately
after parent category. This is helpful when you get categories with custom order
(i.e. alphabetically) and don't want to use recursion to iterate over your categories.

```php
$categories = app('categories.category')->get()->toFlatTree();
```

##### Getting a subtree

Sometimes you don't need whole tree to be loaded and just some subtree of specific category:

```php
$root = app('categories.category')->find($rootId);
$tree = $root->descendants->toTree($root);
```

Now `$tree` contains children of `$root` category.

If you don't need `$root` category itself, do following instead:

```php
$tree = app('categories.category')->descendantsOf($rootId)->toTree($rootId);
```

#### Render HTML dropdown menu
You can render dropdown in various ways:

```php
// render with app()
app('categories.category')::htmlSelect();

// render with model class
Category::htmlSelect();

// return collection instead of array
Category::htmlSelect('collection');

// prepend a key-value item to dropdown
Category::htmlSelect('array', ['Select an item', 0]);

// render dropdown for a specific type
Category::htmlSelect('array', [], Category::ARTICLE_TYPE);
```

### Deleting categories

To delete a category:

```php
$category->delete();
```

**IMPORTANT!** Any descendant that category has will also be **deleted**!

**IMPORTANT!** Categories are required to be deleted as models, **don't** try do delete them using a query like so:

```php
app('categories.category')->where('id', '=', $id)->delete();
```

**That will break the tree!**

`SoftDeletes` trait is supported, also on model level.

### Helper methods

```php
// Check if category is a descendant of other category
$bool = $category->isDescendantOf($parent);

// Check whether the category is a root:
$bool = $category->isRoot();

// Other checks
$category->isChildOf($other);
$category->isAncestorOf($other);
$category->isSiblingOf($other);
```

### Checking consistency

You can check whether a tree is broken (i.e. has some structural errors):

```php
// Check if tree is broken
$bool = app('categories.category')->isBroken();

// Get tree error statistics
$data = app('categories.category')->countErrors();
```

Tree error statistics will return an array with following keys:

- `oddness` -- the number of categories that have wrong set of `lft` and `rgt` values
- `duplicates` -- the number of categories that have same `lft` or `rgt` values
- `wrong_parent` -- the number of categories that have invalid `parent_id` value that doesn't correspond to `lft` and `rgt` values
- `missing_parent` -- the number of categories that have `parent_id` pointing to category that doesn't exists

#### Fixing tree

Category tree can now be fixed if broken. Using inheritance info from `parent_id` column,
proper `_lft` and `_rgt` values are set for every category.

```php
app('categories.category')->fixTree();
```

> **Note:** Check **[Nested Sets](https://github.com/lazychaser/laravel-nestedset)** package for further details.


## Changelog

Refer to the [Changelog](CHANGELOG.md) for a full history of the project.

## License

This software is released under [The MIT License (MIT)](LICENSE).

(c) 2018 Mostafaznv, Some rights reserved.
