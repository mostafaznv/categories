<?php

namespace Mostafaznv\Categories\Traits\Support\Slug;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Sluggable
 * some functions used from https://github.com/spatie/laravel-sluggable
 *
 * @package Mostafaznv\Categories\Traits\Support\Slug
 */
trait Sluggable
{
    protected $lang;

    protected static function bootSluggable()
    {
        static::creating(function (Model $model) {
            $model->generateSlugOnCreate();
        });

        static::updating(function (Model $model) {
            $model->generateSlugOnUpdate();
        });
    }

    /**
     * Handle adding slug on model creation.
     */
    protected function generateSlugOnCreate()
    {
        $this->setLang();

        if (!$this->sluggable['on_create'])
            return;

        $this->addSlug();
    }

    /**
     * Handle adding slug on model update.
     */
    protected function generateSlugOnUpdate()
    {
        $this->setLang();

        if (!$this->sluggable['on_update'])
            return;

        $this->addSlug();
    }

    protected function setLang()
    {
        if (isset($this->sluggable['lang']) and $this->sluggable['lang'])
            $this->lang = $this->sluggable['lang'];
        else
            $this->lang = config('app.locale');
    }

    /**
     * Add the slug to the model.
     */
    protected function addSlug()
    {
        $this->guardAgainstInvalidSlugOptions();

        $from = $this->sluggable['from'];
        $slugField = $this->sluggable['field'];

        if ($this->$slugField)
            $title = $this->$slugField;
        else
            $title = $this->$from;

        $this->$slugField = $this->makeSlugUnique($title);
    }

    /**
     * Make the given slug unique.
     *
     * @param string $title
     * @return string
     */
    protected function makeSlugUnique(string $title): string
    {
        $slug = self::generateSlug($title, $this->sluggable['separator']);
        $originalSlug = $slug;
        $i = 1;

        while ($this->otherRecordExistsWithSlug($slug) || $slug === '')
            $slug = $originalSlug . $this->sluggable['separator'] . $i++;

        return $slug;
    }

    /**
     * Determine if a record exists with the given slug.
     *
     * @param string $slug
     * @return bool
     */
    protected function otherRecordExistsWithSlug(string $slug): bool
    {
        $key = $this->getKey();
        return (bool)static::where($this->sluggable['field'], $slug)->where($this->getKeyName(), '!=', $key)->withoutGlobalScopes()->first();
    }

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     */
    protected function guardAgainstInvalidSlugOptions()
    {
        $errors = [];

        if (!isset($this->sluggable) or empty($this->sluggable))
            throw new Exception("sluggable is required");

        if (!isset($this->sluggable['field']) or !$this->sluggable['field'])
            $errors[] = 'fields';

        if (!isset($this->sluggable['from']) or !$this->sluggable['from'])
            $errors[] = 'from';

        if (!isset($this->sluggable['on_create']))
            $errors[] = 'on_create';

        if (!isset($this->sluggable['on_update']))
            $errors[] = 'on_update';

        if (!isset($this->sluggable['separator']))
            $errors[] = 'separator';

        if (count($errors))
        {
            $errors = implode(', ', $errors);
            throw new Exception("$errors not set or is null");
        }
    }

    /**
     * Removes unsafe characters from file name and convert none english words to english (configurable)
     *
     * @param  string $string Path unsafe file name
     * @param  string $separator
     * @return string         Path Safe file name
     *
     */
    public function generateSlug($string, $separator = '-')
    {
        $string = self::ascii($string, $this->lang);

        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';
        $string = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $string);

        // Replace @ with the word 'at'
        $string = str_replace('@', $separator . 'at' . $separator, $string);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $string = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', mb_strtolower($string));

        // Replace all separator characters and whitespace by a single separator
        $string = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $string);

        return trim($string, $separator);
    }

    /**
     * Transliterate a UTF-8 value to ASCII.
     *
     * Note: Adapted from laravel/framework with some customizations to support farsi and arabic.
     *
     * @see https://github.com/laravel/framework/blob/5.6/README.md
     *
     * @param  string $value
     * @param  string $language
     * @return string
     */
    protected function ascii($value, $language = 'en')
    {
        $languageSpecific = $this->languageSpecificCharsArray($language);

        if (!is_null($languageSpecific))
            $value = str_replace($languageSpecific[0], $languageSpecific[1], $value);

        foreach ($this->charsArray($language) as $key => $val)
            $value = str_replace($val, $key, $value);

        if (in_array($language, ['fa', 'ar']))
            return $this->faRegex($value);

        return preg_replace('/[^\x20-\x7E]/u', '', $value);
    }

    /**
     * Remove unsafe characters except farsi and arabic characters
     *
     * @param $value
     * @return mixed
     */
    protected function faRegex($value)
    {
        return preg_replace('/[^!(|۰|۱|۲|۳|۴|۵|۶|۷|۸|۹|٤|٥|٦|ا|أ|ب|د|ض|إ|ف|گ|ح|ه|ی|ｉ|ج|ج|ق|ك|ک|ل|م|ن|و|پ|ر|س|ص|ت|ط|ي|ز|آ|ع|چ|غ|خ|ؤ|ش|ث|ذ|ظ|هٔ|ة|ئ|اً|ژ|ء|)\x20-\x7E]/u', '', $value);
    }

    /**
     * Returns the language specific replacements for the ascii method.
     *
     * Note: Adapted from Stringy\Stringy
     *
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     *
     * @param  string $language
     * @return array|null
     */
    protected function languageSpecificCharsArray($language)
    {
        static $languageSpecific;

        if (!isset($languageSpecific))
        {
            $languageSpecific = [
                'bg' => [
                    ['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
                    ['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y'],
                ],
                'de' => [
                    ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü'],
                    ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
                ],
            ];
        }

        return $languageSpecific[$language] ?? null;
    }

    /**
     * Returns the replacements for the ascii method.
     *
     * Note: Adapted from Stringy\Stringy with some customizations to support farsi and arabic.
     *
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     *
     * @return array
     */
    protected function charsArray($language)
    {
        static $charsArray;

        if (isset($charsArray))
            return $charsArray;

        if (in_array($language, ['fa', 'ar']))
        {
            return $charsArray = [
                '0' => ['°', '₀', '０'],
                '1' => ['¹', '₁', '１'],
                '2' => ['²', '₂', '２'],
                '3' => ['³', '₃', '３'],
                '4' => ['⁴', '₄', '４'],
                '5' => ['⁵', '₅', '５'],
                '6' => ['⁶', '₆', '６'],
                '7' => ['⁷', '₇', '７'],
                '8' => ['⁸', '₈', '８'],
                '9' => ['⁹', '₉', '９'],
                'a' => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ａ', 'ä'],
                'b' => ['б', 'β', 'ဗ', 'ბ', 'ｂ'],
                'c' => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
                'd' => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'ဍ', 'ဒ', 'დ', 'ｄ'],
                'e' => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'ｅ'],
                'f' => ['ф', 'φ', 'ƒ', 'ფ', 'ｆ'],
                'g' => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'ｇ'],
                'h' => ['ĥ', 'ħ', 'η', 'ή', 'ဟ', 'ှ', 'ჰ', 'ｈ'],
                'i' => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ｉ'],
                'j' => ['ĵ', 'ј', 'Ј', 'ჯ', 'ｊ'],
                'k' => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'က', 'კ', 'ქ', 'ｋ'],
                'l' => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'လ', 'ლ', 'ｌ'],
                'm' => ['м', 'μ', 'မ', 'მ', 'ｍ'],
                'n' => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'န', 'ნ', 'ｎ'],
                'o' => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
                'p' => ['п', 'π', 'ပ', 'პ', 'ｐ'],
                'q' => ['ყ', 'ｑ'],
                'r' => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'რ', 'ｒ'],
                's' => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'စ', 'ſ', 'ს', 'ｓ'],
                't' => ['ť', 'ţ', 'т', 'τ', 'ț', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ'],
                'u' => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
                'v' => ['в', 'ვ', 'ϐ', 'ｖ'],
                'w' => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
                'x' => ['χ', 'ξ', 'ｘ'],
                'y' => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ယ', 'ｙ'],
                'z' => ['ź', 'ž', 'ż', 'з', 'ζ', 'ဇ', 'ზ', 'ｚ'],
                'aa' => ['आ'],
                'ae' => ['æ', 'ǽ'],
                'ai' => ['ऐ'],
                'ch' => ['ч', 'ჩ', 'ჭ'],
                'dj' => ['ђ', 'đ'],
                'dz' => ['џ', 'ძ'],
                'ei' => ['ऍ'],
                'gh' => ['ღ'],
                'ii' => ['ई'],
                'ij' => ['ĳ'],
                'kh' => ['х', 'ხ'],
                'lj' => ['љ'],
                'nj' => ['њ'],
                'oe' => ['ö', 'œ'],
                'oi' => ['ऑ'],
                'oii' => ['ऒ'],
                'ps' => ['ψ'],
                'sh' => ['ш', 'შ'],
                'shch' => ['щ'],
                'ss' => ['ß'],
                'sx' => ['ŝ'],
                'th' => ['þ', 'ϑ'],
                'ts' => ['ц', 'ც', 'წ'],
                'ue' => ['ü'],
                'uu' => ['ऊ'],
                'ya' => ['я'],
                'yu' => ['ю'],
                'zh' => ['ж', 'ჟ'],
                '(c)' => ['©'],
                'A' => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
                'B' => ['Б', 'Β', 'ब', 'Ｂ'],
                'C' => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
                'D' => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
                'E' => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
                'F' => ['Ф', 'Φ', 'Ｆ'],
                'G' => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
                'H' => ['Η', 'Ή', 'Ħ', 'Ｈ'],
                'I' => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
                'J' => ['Ｊ'],
                'K' => ['К', 'Κ', 'Ｋ'],
                'L' => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
                'M' => ['М', 'Μ', 'Ｍ'],
                'N' => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
                'O' => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
                'P' => ['П', 'Π', 'Ｐ'],
                'Q' => ['Ｑ'],
                'R' => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
                'S' => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
                'T' => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
                'U' => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
                'V' => ['В', 'Ｖ'],
                'W' => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
                'X' => ['Χ', 'Ξ', 'Ｘ'],
                'Y' => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
                'Z' => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
                'AE' => ['Æ', 'Ǽ'],
                'Ch' => ['Ч'],
                'Dj' => ['Ђ'],
                'Dz' => ['Џ'],
                'Gx' => ['Ĝ'],
                'Hx' => ['Ĥ'],
                'Ij' => ['Ĳ'],
                'Jx' => ['Ĵ'],
                'Kh' => ['Х'],
                'Lj' => ['Љ'],
                'Nj' => ['Њ'],
                'Oe' => ['Œ'],
                'Ps' => ['Ψ'],
                'Sh' => ['Ш'],
                'Shch' => ['Щ'],
                'Ss' => ['ẞ'],
                'Th' => ['Þ'],
                'Ts' => ['Ц'],
                'Ya' => ['Я'],
                'Yu' => ['Ю'],
                'Zh' => ['Ж'],
                ' ' => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80", "\xEF\xBE\xA0"],
            ];
        }
        else
        {
            return $charsArray = [
                '0' => ['°', '₀', '０'],
                '1' => ['¹', '₁', '１'],
                '2' => ['²', '₂', '２'],
                '3' => ['³', '₃', '３'],
                '4' => ['⁴', '₄', '٤', '４'],
                '5' => ['⁵', '₅', '٥', '５'],
                '6' => ['⁶', '₆', '٦', '６'],
                '7' => ['⁷', '₇', '７'],
                '8' => ['⁸', '₈', '８'],
                '9' => ['⁹', '₉', '９'],
                'a' => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä'],
                'b' => ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ'],
                'c' => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
                'd' => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ'],
                'e' => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
                'f' => ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ'],
                'g' => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ'],
                'h' => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ'],
                'i' => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ی', 'ｉ'],
                'j' => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
                'k' => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ'],
                'l' => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ'],
                'm' => ['м', 'μ', 'م', 'မ', 'მ', 'ｍ'],
                'n' => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ'],
                'o' => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
                'p' => ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ'],
                'q' => ['ყ', 'ｑ'],
                'r' => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ'],
                's' => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ'],
                't' => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ'],
                'u' => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
                'v' => ['в', 'ვ', 'ϐ', 'ｖ'],
                'w' => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
                'x' => ['χ', 'ξ', 'ｘ'],
                'y' => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
                'z' => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ'],
                'aa' => ['ع', 'आ', 'آ'],
                'ae' => ['æ', 'ǽ'],
                'ai' => ['ऐ'],
                'ch' => ['ч', 'ჩ', 'ჭ', 'چ'],
                'dj' => ['ђ', 'đ'],
                'dz' => ['џ', 'ძ'],
                'ei' => ['ऍ'],
                'gh' => ['غ', 'ღ'],
                'ii' => ['ई'],
                'ij' => ['ĳ'],
                'kh' => ['х', 'خ', 'ხ'],
                'lj' => ['љ'],
                'nj' => ['њ'],
                'oe' => ['ö', 'œ', 'ؤ'],
                'oi' => ['ऑ'],
                'oii' => ['ऒ'],
                'ps' => ['ψ'],
                'sh' => ['ш', 'შ', 'ش'],
                'shch' => ['щ'],
                'ss' => ['ß'],
                'sx' => ['ŝ'],
                'th' => ['þ', 'ϑ', 'ث', 'ذ', 'ظ'],
                'ts' => ['ц', 'ც', 'წ'],
                'ue' => ['ü'],
                'uu' => ['ऊ'],
                'ya' => ['я'],
                'yu' => ['ю'],
                'zh' => ['ж', 'ჟ', 'ژ'],
                '(c)' => ['©'],
                'A' => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
                'B' => ['Б', 'Β', 'ब', 'Ｂ'],
                'C' => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
                'D' => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
                'E' => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
                'F' => ['Ф', 'Φ', 'Ｆ'],
                'G' => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
                'H' => ['Η', 'Ή', 'Ħ', 'Ｈ'],
                'I' => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
                'J' => ['Ｊ'],
                'K' => ['К', 'Κ', 'Ｋ'],
                'L' => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
                'M' => ['М', 'Μ', 'Ｍ'],
                'N' => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
                'O' => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
                'P' => ['П', 'Π', 'Ｐ'],
                'Q' => ['Ｑ'],
                'R' => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
                'S' => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
                'T' => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
                'U' => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
                'V' => ['В', 'Ｖ'],
                'W' => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
                'X' => ['Χ', 'Ξ', 'Ｘ'],
                'Y' => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
                'Z' => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
                'AE' => ['Æ', 'Ǽ'],
                'Ch' => ['Ч'],
                'Dj' => ['Ђ'],
                'Dz' => ['Џ'],
                'Gx' => ['Ĝ'],
                'Hx' => ['Ĥ'],
                'Ij' => ['Ĳ'],
                'Jx' => ['Ĵ'],
                'Kh' => ['Х'],
                'Lj' => ['Љ'],
                'Nj' => ['Њ'],
                'Oe' => ['Œ'],
                'Ps' => ['Ψ'],
                'Sh' => ['Ш'],
                'Shch' => ['Щ'],
                'Ss' => ['ẞ'],
                'Th' => ['Þ'],
                'Ts' => ['Ц'],
                'Ya' => ['Я'],
                'Yu' => ['Ю'],
                'Zh' => ['Ж'],
                ' ' => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80", "\xEF\xBE\xA0"],
            ];
        }
    }
}