<template>
    <div id="recent">
        <div class="subheader d-flex">
            <span class="subheader-title">Recent</span>
        </div>

        <div id="recent-list" class="pa-4">
            <!-- Loading state -->
            <div v-if="loading" class="text-center py-8">
                <v-progress-circular indeterminate></v-progress-circular>
            </div>

            <!-- Error state -->
            <div v-else-if="error" class="text-center py-8">
                <v-icon size="64" class="mb-4" color="error">mdi-alert-circle</v-icon>
                <div class="text-h6">Failed to load recent chapters</div>
                <v-btn class="mt-4" @click="loadRecentChapters">Retry</v-btn>
            </div>

            <!-- Empty state -->
            <div v-else-if="chapters.length === 0" class="text-center py-8">
                <v-icon size="64" class="mb-4">mdi-book-open-page-variant</v-icon>
                <div class="text-h6">No recently opened chapters</div>
                <div class="text-body-2 mt-2">
                    Chapters you read will appear here
                </div>
            </div>

            <!-- Chapter list -->
            <v-list v-else two-line>
                <v-list-item
                    v-for="chapter in chapters"
                    :key="chapter.id"
                    @click="openChapter(chapter.id)"
                    class="recent-item"
                >
                    <v-list-item-content>
                        <v-list-item-title class="font-weight-medium">
                            {{ chapter.name }}
                        </v-list-item-title>
                        <v-list-item-subtitle>
                            {{ chapter.book ? chapter.book.name : 'Unknown book' }}
                            <span class="ml-2 text-caption">
                                {{ formatDate(chapter.opened_at) }}
                            </span>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                    <v-list-item-action>
                        <v-btn icon @click.stop="openChapter(chapter.id)">
                            <v-icon>mdi-book-open-variant</v-icon>
                        </v-btn>
                    </v-list-item-action>
                </v-list-item>
            </v-list>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        language: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            chapters: [],
            loading: true,
            error: false,
        };
    },
    methods: {
        loadRecentChapters() {
            this.loading = true;
            this.error = false;
            axios.post('/chapters/recent', {
                language: this.language,
                limit: 50
            }).then((response) => {
                this.chapters = response.data;
            }).catch((error) => {
                console.error('Failed to load recent chapters:', error);
                this.error = true;
            }).finally(() => {
                this.loading = false;
            });
        },
        openChapter(chapterId) {
            this.$router.push({ path: '/chapters/read/' + chapterId });
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;

            // Less than 1 hour
            if (diff < 3600000) {
                const minutes = Math.floor(diff / 60000);
                return minutes <= 1 ? 'Just now' : `${minutes} min ago`;
            }
            // Less than 24 hours
            if (diff < 86400000) {
                const hours = Math.floor(diff / 3600000);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            }
            // Less than 7 days
            if (diff < 604800000) {
                const days = Math.floor(diff / 86400000);
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }
            // Otherwise show date
            return date.toLocaleDateString();
        }
    },
    watch: {
        language: {
            handler() {
                this.loadRecentChapters();
            },
            immediate: true
        }
    }
};
</script>
