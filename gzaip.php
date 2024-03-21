<?php

require __DIR__ . '/vendor/autoload.php';

use Ds\Vector;

class Book
{
    public function __construct($title, $genre)
    {
        $this->title_m = $title;
        $this->genre_m = $genre;
    }

    public function title() { return $this->title_m; }
    public function genre() { return $this->genre_m; }

    private string $title_m;
    private string $genre_m;
}

class BookNCD
{
    public function __construct($genre, $distance)
    {
        $this->genre_m    = $genre;
        $this->distance_m = $distance;
    }

    public function genre() { return $this->genre_m; }
    public function distance() { return $this->distance_m; }

    private string $genre_m;
    private float $distance_m;
}

const AVAILABLE_GENRES = [
    "Arts & Photography",
    "Biographies & Memoirs",
    "Business & Money",
    "Calendars",
    "Children's Books",
    "Comics & Graphic Novels",
    "Computers & Technology",
    "Cookbooks, Food & Wine",
    "Crafts, Hobbies & Home",
    "Christian Books & Bibles",
    "Engineering & Transportation",
    "Health, Fitness & Dieting",
    "History",
    "Humor & Entertainment",
    "Law",
    "Literature & Fiction",
    "Medical Books",
    "Mystery, Thriller & Suspense",
    "Parenting & Relationships",
    "Politics & Social Sciences",
    "Reference",
    "Religion & Spirituality",
    "Romance",
    "Science & Math",
    "Science Fiction & Fantasy",
    "Self-Help",
    "Sports & Outdoors",
    "Teen & Young Adult",
    "Test Preparation",
    "Travel",
    "Gay & Lesbian",
    "Education & Teaching",
];

function parse_dataset_from_file($path): Vector
{
    $result  = new Vector();
    $dataset = fopen($path, 'r');

    while (true)
    {
        $data = fgetcsv($dataset);

        if (feof($dataset))
        {
            break;
        }

        $result->push(new Book("{$data[3]}", "{$data[6]}"));
    }

    fclose($dataset);

    return $result;
}

function calculate_title_ncd($lhsTitle, $rhsTitle): float
{
    $xy = gzdeflate($lhsTitle . " " . $rhsTitle);
    assert($xy !== false);
    $xyLen = strlen($xy);

    $x = gzdeflate($lhsTitle);
    assert($x !== false);
    $xLen = strlen($x);

    $y = gzdeflate($rhsTitle);
    assert($y !== false);
    $yLen = strlen($y);

    return ($xyLen - min([$xLen, $yLen])) / max([$xLen, $yLen]);
}

function calculate_title_ncds($title, $books): Vector
{
    $result = new Vector();

    foreach ($books as $book)
    {
        $titleNCD = calculate_title_ncd(lhsTitle: $title, rhsTitle: $book->title());
        $result->push(new BookNCD(genre: $book->genre(), distance: $titleNCD));
    }

    $result->sort(function ($lhs, $rhs) {
        return $lhs->distance() > $rhs->distance();
    });

    return $result;
}

function calculate_klass_frequencies($title, $dataset, $k): SplFixedArray
{
    $result = new SplFixedArray(sizeof(AVAILABLE_GENRES));
    $ncds   = calculate_title_ncds($title, $dataset);

    for ($index = 0; $index != $ncds->count() && $index < $k; $index += 1)
    {
        $genreIndex = array_search(needle: $ncds[$index]->genre(), haystack: AVAILABLE_GENRES);
        assert($genreIndex !== false);
        $result[$genreIndex] += 1;
    }

    return $result;
}

function klassify_book_title($title, $dataset, $k = 1000): string
{
    $klassFrequencies = calculate_klass_frequencies($title, $dataset, $k);
    $prediction       = 0;

    for ($index = 0; $index != sizeof(AVAILABLE_GENRES); $index += 1)
    {
        if ($klassFrequencies[$prediction] < $klassFrequencies[$index])
        {
            $prediction = $index;
        }
    }

    return AVAILABLE_GENRES[$prediction];
}

function main()
{
    $dataset = parse_dataset_from_file('datasets/book32-listing.csv');
    $title   = 'A Classical Introduction to Modern Number Theory';

    echo "title: {$title}\n";
    echo "predicated klass: " . klassify_book_title($title, $dataset) . "\n";
}

main();

?>
