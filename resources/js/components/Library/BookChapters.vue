<template>
    <v-container class="book-chapters py-0">
        <!-- Error dialog -->
        <error-dialog
            v-if="errorDialog.active"
            v-model="errorDialog.active" 
            content="An error has occurred while deleting the chapter."
        ></error-dialog>

        <!-- Edit book chapter dialog -->
        <edit-book-chapter-dialog
            v-if="editBookChapterDialog.active"
            v-model="editBookChapterDialog.active" 
            :book-id="$props.bookId"
            :chapter-id="editBookChapterDialog.chapterId"
            @chapter-saved="chapterSaved"
        >
        </edit-book-chapter-dialog>

        <!-- Delete book chapter dialog -->
        <delete-book-chapter-dialog
            v-if="deleteBookChapterDialog.active"
            v-model="deleteBookChapterDialog.active" 
            :chapter-id="deleteBookChapterDialog.chapterId"
            :chapter-name="deleteBookChapterDialog.chapterName"
            @confirm="deleteChapter"
        >
        </delete-book-chapter-dialog>
        
        <!-- Review dialog -->
        <start-review-dialog 
            v-model="startReviewDialog.active" 
            :book-id="startReviewDialog.bookId" 
            :book-name="startReviewDialog.bookName"
            :chapter-id="startReviewDialog.chapterId" 
            :chapter-name="startReviewDialog.chapterName">
        </start-review-dialog>
        

        <!-- Chapter list -->
        <v-data-table
            class="my-4 mb-0 no-hover"
            :headers="[
                { text: 'Chapter', value: 'name'},
                { text: 'Total', value: 'wordCount.total', align: 'center' },
                { text: 'Unique', value: 'wordCount.unique', align: 'center' },
                { text: 'Known', value: 'wordCount.known', align: 'center' },
                { text: 'Highlighted', value: 'wordCount.highlighted', align: 'center' },
                { text: 'New', value: 'wordCount.new', align: 'center' },
                { text: 'Actions', value: 'actions', sortable: false },
            ]"
            :items="chapters"
            :loading="chaptersLoading"
            :page="page"
            :items-per-page="itemsPerPage"
            :footer-props="{ 'items-per-page-options': [25, 50, 100, -1] }"
            :hide-default-footer="chapters.length <= 25"
            @update:page="pageChanged"
            @update:items-per-page="itemsPerPageChanged"
        >

            <!-- Chapter name and reading status -->
            <template v-slot:item.name="{ item }">
                {{ item.name }}
                <v-icon v-if="item.read_count > 0" small color="success" class="ml-1" title="Finished">mdi-check-circle</v-icon>
                <v-icon v-else-if="item.opened_at" small class="ml-1" title="Started">mdi-clock-outline</v-icon>
            </template>

            <!-- Total words -->
            <template v-slot:item.wordCount.total="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    {{ formatNumber(item.wordCount.total) }}
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Unique words -->
            <template v-slot:item.wordCount.unique="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    {{ formatNumber(item.wordCount.unique) }}
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Known words -->
            <template v-slot:item.wordCount.known="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    <template v-if="$props.wordCountDisplayType == 0">
                        {{ formatNumber(item.wordCount.known) }}
                    </template>
                    <template v-else-if="item.wordCount.unique">
                        {{ (item.wordCount.known / item.wordCount.unique * 100).toFixed(1) }}%
                    </template>
                    <template v-else>
                        0%
                    </template>
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Highlighted words -->
            <template v-slot:item.wordCount.highlighted="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    <div class="highlighted-words px-2 rounded-xl mx-auto">
                        <template v-if="$props.wordCountDisplayType < 2">
                            {{ formatNumber(item.wordCount.highlighted) }}
                        </template>
                        <template v-else-if="item.wordCount.unique">
                            {{ (item.wordCount.highlighted / item.wordCount.unique * 100).toFixed(1) }}%
                        </template>
                        <template v-else>
                            0%
                        </template>
                    </div>
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>


            <!-- New words -->
            <template v-slot:item.wordCount.new="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    <div class="new-words px-2 rounded-xl mx-auto">
                        <template v-if="$props.wordCountDisplayType < 2">
                            {{ formatNumber(item.wordCount.new) }}
                        </template>
                        <template v-else-if="item.wordCount.unique">
                            {{ (item.wordCount.new / item.wordCount.unique * 100).toFixed(1) }}%
                        </template>
                        <template v-else>
                            0%
                        </template>
                    </div>
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Actions -->
            <template v-slot:item.actions="{ item }">
                <div class="d-flex justify-center">
                    <!-- Action buttons -->
                    <template v-if="item.processing_status == 'processed'">
                        <v-btn icon :to="'/chapters/read/' + item.id" title="Read"><v-icon>mdi-book-open-variant</v-icon></v-btn>
                        <v-menu rounded offset-y bottom left nudge-top="-5">
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn icon v-bind="attrs" v-on="on"><v-icon>mdi-dots-horizontal</v-icon></v-btn>
                            </template>
                            <v-btn
                                width="100"
                                class="menu-button"
                                tile
                                color="white"
                                @click="showEditChapterDialog(item.id)"
                            >
                                Edit
                            </v-btn>
                            <v-btn
                                width="100"
                                class="menu-button"
                                tile
                                color="white"
                                @click="showStartReviewDialog(book.id, book.name, item.id, item.name)"
                            >
                                Review
                            </v-btn>
                            <v-btn
                                width="100"
                                class="menu-button"
                                tile
                                color="white"
                                @click="showDeleteChapterDialog(item)"
                            >
                                Delete
                            </v-btn>
                        </v-menu>
                    </template>

                    <!-- Chapter importing loader -->
                    <template v-else-if="item.processing_status === 'unprocessed'">
                        <v-chip small color="warning">importing</v-chip>
                    </template>

                    <!-- Chapter importing failed -->
                    <template v-else-if="item.processing_status === 'failed'">
                        <v-chip small color="error">failed</v-chip>
                    </template>
                </div>
            </template>
        </v-data-table>
    </v-container>
</template>

<script>
    import {formatNumber} from './../../helper.js';
    export default {
        data: function() {
            return {
                book: null,
                bookWordCount: null,
                chapters: [],
                chaptersLoading: false,
                page: 1,
                itemsPerPage: this.loadStoredNumber('book-chapters-items-per-page', 25),
                randomChapter: 0,
                errorDialog: {
                    active: false,
                },
                editBookChapterDialog: {
                    active: false,
                    chapterId: -1,
                },
                deleteBookChapterDialog: {
                    active: false,
                    chapterId: -1,
                },
                startReviewDialog: {
                    active: false,
                    bookId: -1,
                    bookName: '',
                    chapterId: -1,
                    chapterName: '',
                }
            }
        },
        props: {
            bookId: Number,
            wordCountDisplayType: Number,
        },
        mounted() {
            this.loadChapters();

            // retrieve word counts
            this.$store.getters['shared/echo'].private('chapter-status-update.' + this.$store.getters['shared/userUuid']).listen('ChapterStateUpdatedEvent', (message) => {
                this.chapterStatusUpdate(JSON.parse(message.chapters));
            });
        },
        beforeDestroy() {
            this.$store.getters['shared/echo'].private('chapter-status-update.' + this.$store.getters['shared/userUuid']).stopListening('ChapterStateUpdatedEvent');
        },
        methods: {
            chapterStatusUpdate(chapters) {
                this.chapters.forEach((currentChapter) => {
                    if (!chapters[currentChapter.id]) {
                        return;
                    }

                    if ('wordCount' in chapters[currentChapter.id] && chapters[currentChapter.id].wordCount !== null) {
                        currentChapter.wordCount = chapters[currentChapter.id].wordCount
                        currentChapter.wordCountsLoaded = true;
                    }

                    if ('processing_status' in chapters[currentChapter.id]) {
                        currentChapter.processing_status = chapters[currentChapter.id].processing_status;
                    }
                });
            },
            chapterSaved() {
                this.$emit('word-count-changed');
            },
            showEditChapterDialog(chapterId) {
                this.editBookChapterDialog.active = true;
                this.editBookChapterDialog.chapterId = chapterId;
            },
            showDeleteChapterDialog(chapter) {
                this.deleteBookChapterDialog.active = true;
                this.deleteBookChapterDialog.chapterId = chapter.id;
                this.deleteBookChapterDialog.chapterName = chapter.name;
            },
            deleteChapter() {
                axios.post('/chapters/delete', {
                    'chapterId': this.deleteBookChapterDialog.chapterId,
                }).catch(() => {
                    this.errorDialog.active = true;
                }).then((response) => {
                    if (response.status === 200) {
                        this.$emit('word-count-changed');
                    } else {
                        this.errorDialog.active = true;
                    }
                });
            },
            loadChapters() {
                this.chaptersLoading = true;
                this.chapters = [];

                axios.post('/chapters', {
                    'bookId': this.$props.bookId,
                }).then((response) => {
                    for (let chapterIndex = 0; chapterIndex < response.data.chapters.length; chapterIndex++) {
                        response.data.chapters[chapterIndex].wordCountsLoaded = false;
                    }
                    
                    this.book = response.data.book;
                    this.chapters = response.data.chapters;

                    if (this.chapters.length) {
                        this.randomChapter = this.chapters[Math.floor(Math.random() * this.chapters.length)].id;
                    } else {
                        this.randomChapter = 0;
                    }

                    this.chaptersLoading = false;
                    this.$nextTick(() => {
                        // restore the page the user was on for this book
                        this.page = this.loadStoredNumber('book-chapters-page-' + this.$props.bookId, 1);
                        axios.get('/chapters/word-counts/' + this.$props.bookId);
                    }) 
                });
            },
            pageChanged(page) {
                // the table resets its page while the list is empty, do not store that
                if (this.chaptersLoading || !this.chapters.length) {
                    return;
                }

                this.page = page;
                this.storeNumber('book-chapters-page-' + this.$props.bookId, page);
            },
            itemsPerPageChanged(itemsPerPage) {
                this.itemsPerPage = itemsPerPage;
                this.storeNumber('book-chapters-items-per-page', itemsPerPage);
            },
            loadStoredNumber(key, defaultValue) {
                try {
                    const value = parseInt(localStorage.getItem(key), 10);
                    return Number.isNaN(value) ? defaultValue : value;
                } catch (error) {
                    return defaultValue;
                }
            },
            storeNumber(key, value) {
                try {
                    localStorage.setItem(key, value);
                } catch (error) {
                    // storage unavailable, the setting just will not persist
                }
            },
            showStartReviewDialog(bookId, bookName, chapterId, chapterName) {
                this.startReviewDialog.bookName = bookName;
                this.startReviewDialog.bookId = bookId;
                this.startReviewDialog.chapterName = chapterName;
                this.startReviewDialog.chapterId = chapterId;
                this.startReviewDialog.active = true;
            },
            formatNumber: formatNumber
        }
    }
</script>
