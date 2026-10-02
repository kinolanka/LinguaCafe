<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Book;

use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Enums\ChapterProcessingStatusEnum;
use Illuminate\Support\Facades\Storage;

class BookService {
    
    public function __construct() {
    }
    
    public function getBooks($userId, $language) {
        $books = Book
            ::where('user_id', $userId)
            ->where('language', $language)
            ->orderBy('updated_at', 'DESC')
            ->get();

        // chapters of all books, used to find the chapter the user is currently reading
        $chapters = Chapter
            ::select(['id', 'name', 'book_id', 'read_count', 'processing_status'])
            ->where('user_id', $userId)
            ->whereIn('book_id', $books->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('book_id');

        foreach ($books as $book) {
            // sets initial value used by vue in the library
            $book->wordCount = null;

            // current chapter is the first processed chapter after the last finished one
            $currentChapter = null;
            foreach ($chapters->get($book->id, []) as $chapter) {
                if ($chapter->read_count > 0) {
                    $currentChapter = null;
                } else if ($currentChapter === null && $chapter->processing_status === ChapterProcessingStatusEnum::PROCESSED->value) {
                    $currentChapter = $chapter;
                }
            }

            $book->currentChapter = $currentChapter === null ? null : [
                'id' => $currentChapter->id,
                'name' => $currentChapter->name,
            ];
        }

        return $books;
    }


    public function getBookWordCounts($userId, $bookId) {
        $book = Book
            ::where('user_id', $userId)
            ->where('id', $bookId)
            ->first();
                
        if (!$book) {
            throw new \Exception('Book does not exist, or it belongs to a different user.');
        }
        
        // get words for calculating word counts
        $words = EncounteredWord
            ::select(['id', 'word', 'stage'])
            ->where('user_id', $userId)
            ->where('language', $book->language)
            ->get()
            ->keyBy('id')
            ->toArray();

        // calculate word counts
        return $book->getWordCounts($userId, $words);
    }

    /*
        Updates the word count of the book. This only stores the length of the book,
        other word counts are being calculated in real time.
    */

    public function updateBookWordCount($userId, $bookId) {
        // calculate book word count
        $bookWordCount = Chapter
            ::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value)
            ->sum('word_count');

        $bookWordCount = intval($bookWordCount);

        // update book word count
        Book
            ::where('user_id', $userId)
            ->where('id', $bookId)
            ->update(['word_count' => $bookWordCount]);
    }

    public function createBook($userId, $selectedLanguage, $bookName, $bookCoverFile) {
        // create book model
        $book = new Book();
        $book->user_id = $userId;
        $book->cover_image = null;
        $book->language = $selectedLanguage;
        $book->name = $bookName;

        // save new book
        $book->save();
        
        // update image
        if (!is_null($bookCoverFile)) {
            $this->saveBookImage($book, $bookCoverFile);
        }

        return true;
    }

    public function updateBook($userId, $bookId, $bookName, $bookCoverFile) {
        $book = Book
            ::where('user_id', $userId)
            ->where('id', $bookId)
            ->first();

        if (!$book) {
            throw new \Exception('Book does not exist, or it belongs to a different user.');
        }

        // update and save book
        $book->name = $bookName;
        $book->save();
        
        // update image
        if (!is_null($bookCoverFile)) {
            $this->saveBookImage($book, $bookCoverFile);
        }

        return true;
    }

    private function saveBookImage($book, $bookCoverFile) {
        // delete old image
        if ($book->cover_image !== '' && $book->cover_image !== null) {
            Storage::delete('/images/book_images/' . $book->cover_image);
        }

        // save image on server
        $timestamp = implode('_', explode(' ', Carbon::now()->toDateTimeString()));
        $fileName = $book->id . '_' . $timestamp . '.' . ($bookCoverFile->getClientOriginalExtension());
        $bookCoverFile->storeAs('/images/book_images/', $fileName);

        // save image in database
        $book->cover_image = $fileName;
        $book->save();
    }

    public function deleteBook($userId, $bookId) {
        $book = Book
            ::where('user_id', $userId)
            ->where('id', $bookId)
            ->first();

        if (!$book) {
            throw new \Exception('Book does not exist, or it belongs to a different user.');
        }

        Chapter
            ::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->delete();
            
        if ($book->cover_image !== '' && $book->cover_image !== null) {
            Storage::delete('/images/book_images/' . $book->cover_image);
        }

        $book->delete();

        return true;
    }
}