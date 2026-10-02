<template>
    <div id="vocabulary-search-box" class="border rounded-lg pa-2" :language="$props.language">
        <!-- API dictionaries, searched only on demand -->
        <div
            class="search-result"
            v-for="apiDictionary in apiDictionaries"
            :key="`api-dictionary-${apiDictionary.id}`"
        >
            <div class="search-result-title">
                <div class="dictionary-title-icon mr-1" :style="{'background-color': apiDictionary.color}">
                    <v-icon small>mdi-translate</v-icon>
                </div>
                {{ apiDictionary.name }} <div class="search-result-word default-font" :title="$props.searchTerm">{{ $props.searchTerm }}</div>
            </div>

            <!-- Api search loading -->
            <div class="search-result-api-action" v-if="apiSearchResults[apiDictionary.id] && apiSearchResults[apiDictionary.id].loading">
                <v-progress-circular indeterminate size="20" width="3" color="primary"></v-progress-circular>
            </div>

            <!-- Api search result -->
            <template v-else-if="apiSearchResults[apiDictionary.id]">
                <div
                    v-for="(definition, definitionIndex) in apiSearchResults[apiDictionary.id].definitions"
                    :key="`api-search-result-${apiDictionary.id}-${definitionIndex}`"
                    class="search-result-definition rounded"
                    @click="addDefinitionToInput(definition)"
                >
                    {{ definition }} <v-icon small>mdi-plus</v-icon>
                </div>
                <div class="search-result-api-action" v-if="!apiSearchResults[apiDictionary.id].definitions.length">
                    No results
                </div>
            </template>

            <!-- Api search button -->
            <div class="search-result-api-action" v-else>
                <v-btn
                    small
                    rounded
                    depressed
                    :disabled="$props.searchTerm == ''"
                    @click="makeApiSearchRequest(apiDictionary.id)"
                >
                    <v-icon small left>mdi-translate</v-icon>
                    Look up
                </v-btn>
            </div>
        </div>

        <!-- Dictionary loading -->
        <div class="search-result disabled" v-if="dictionarySearchLoading">
            <div class="search-result-title">
                <div class="dictionary-title-icon mr-1" style="background-color: var(--v-primary-base);">
                    <v-icon small>mdi-list-box</v-icon>
                </div>
                <span class="default-font" :title="$props.searchTerm">{{ $props.searchTerm }}</span> <div class="search-result-word">Dictionary search</div>
            </div>

            <div class="search-result-definition rounded pr-2">
                loading <v-progress-circular indeterminate class="ml-1" size="20" width="3" color="primary"></v-progress-circular>
            </div>
        </div> 

        <!-- Dictionary no result message -->
        <div class="search-result disabled" v-if="!dictionarySearchLoading && !dictionarySearchResultsFound">
            <div class="search-result-title default-font" :title="$props.searchTerm">
                <div class="dictionary-title-icon mr-1" style="background-color: var(--v-primary-base);">
                    <v-icon small>mdi-list-box</v-icon>
                </div>
                {{ $props.searchTerm }}
            </div>

            <div class="search-result-definition rounded pr-2">
                No dictionary results
            </div>
        </div> 
        
        <!-- Dictionary search results -->
        <div class="search-result jmdict" v-for="(searchResult, searchresultIndex) in searchResults" :key="searchresultIndex">
            <!-- Regular record -->
            <template v-if="searchResult.dictionary !== 'JMDict'">
                <div v-for="(record, recordIndex) in searchResult.records" :key="recordIndex">
                    <div class="search-result-title" :title="record.word">
                        <div class="dictionary-title-icon mr-1"  :style="{'background-color': searchResult.color}">
                            <v-icon small>mdi-list-box</v-icon>
                        </div>
                        {{ searchResult.dictionary}}<div class="search-result-word" :title="record.word"> {{ record.word }} </div>
                    </div>

                    <div 
                        v-for="(definition, definitionIndex) in record.definitions" 
                        :key="definitionIndex" 
                        class="search-result-definition rounded"
                        @click="addDefinitionToInput(definition)"
                    >
                        {{ definition }} <v-icon small>mdi-plus</v-icon>
                    </div>
                </div>
            </template>

            <!-- JMDict record -->
            <template v-if="searchResult.dictionary == 'JMDict'">
                <div v-for="(record, recordIndex) in searchResult.records" :key="recordIndex">
                    <div class="search-result-title" :title="record.word">
                        <div class="dictionary-title-icon mr-1"  :style="{'background-color': searchResult.color}">
                            <v-icon small>mdi-list-box</v-icon>
                        </div>
                        {{ searchResult.dictionary}}<div class="search-result-word default-font" :title="record.word"> {{ record.word }} </div>
                    </div>
                    
                    <div class="search-result-definition rounded" v-for="(definition, definitionIndex) in record.definitions" :key="definitionIndex" @click="addDefinitionToInput(definition)">
                        {{ definition }} <v-icon small>mdi-plus</v-icon>
                    </div>
                
                    <template v-if="record.otherForms.length">
                        <div class="vocab-box-subheader">Other forms:</div>
                        <div class="d-flex flex-wrap default-font">
                            <div v-for="(form, formIndex) in record.otherForms" :key="formIndex">
                                {{ form }}<span class="mr-2" v-if="formIndex < record.otherForms.length - 1">, </span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
    // enabled api dictionaries are the same for every search box, so they are loaded only once
    let apiDictionariesRequest = null;

    export default {
        props: {
            language: String,
            anyApiDictionaryEnabled: Boolean,
            apiOnly: Boolean,
            searchTerm: String
        },
        watch: { 
            searchTerm: function(newVal, oldVal) {
                this.makeSearchRequest();
            },
            apiOnly: function() {
                this.makeSearchRequest();
            }
        },
        data: function() {
            return {
                searchResults: [],
                dictionarySearchLoading: false,
                dictionarySearchResultsFound: true,
                apiDictionaries: [],
                apiSearchResults: {},
            };
        },
        mounted: function() {
            this.loadApiDictionaries();
            this.makeSearchRequest();
        },
        methods: {
            addDefinitionToInput(definition) {
                this.$emit('addDefinitionToInput', definition);
            },
            makeSearchRequest() {
                this.searchResults = [];
                this.apiSearchResults = {};
                if (this.$props.searchTerm == '') {
                    return;
                }

                // imported dictionaries are not searched for long texts
                if (this.$props.apiOnly) {
                    this.dictionarySearchLoading = false;
                    this.dictionarySearchResultsFound = true;
                    return;
                }

                // dictionary search
                this.dictionarySearchLoading = true;
                this.dictionarySearchResultsFound = false;
                axios.post('/dictionaries/search', {
                    language: this.$props.language,
                    term: this.$props.searchTerm
                }).then((response) => {
                    this.processVocabularySearchResults(response.data);
                    this.dictionarySearchLoading = false;
                });
            },
            loadApiDictionaries() {
                if (apiDictionariesRequest === null) {
                    apiDictionariesRequest = axios.get('/dictionaries/api/list').then((response) => response.data);
                    apiDictionariesRequest.catch(() => {
                        apiDictionariesRequest = null;
                    });
                }

                apiDictionariesRequest.then((dictionaries) => {
                    this.apiDictionaries = dictionaries;
                }).catch(() => {
                    this.apiDictionaries = [];
                });
            },
            makeApiSearchRequest(dictionaryId) {
                const term = this.$props.searchTerm;
                if (term == '') {
                    return;
                }

                this.$set(this.apiSearchResults, dictionaryId, { loading: true, definitions: [] });

                axios.post('/dictionaries/api/search', {
                    language: this.$props.language,
                    term: term,
                    dictionaryId: dictionaryId
                }).then((response) => {
                    // the search term has changed while the request was running
                    if (term !== this.$props.searchTerm) {
                        return;
                    }

                    let definitions = [];
                    response.data.forEach((item) => {
                        definitions = definitions.concat(item.definitions);
                    });

                    this.$set(this.apiSearchResults, dictionaryId, { loading: false, definitions: definitions });
                }).catch(() => {
                    if (term !== this.$props.searchTerm) {
                        return;
                    }

                    this.$set(this.apiSearchResults, dictionaryId, { loading: false, definitions: ['error'] });
                });
            },
            processVocabularySearchResults(data) {
                this.searchResults = [];

                for (var dictionaryIndex = 0; dictionaryIndex < data.length; dictionaryIndex++) {
                    if (data[dictionaryIndex].name == 'JMDict') {
                        let searchResult = {
                            dictionary: data[dictionaryIndex].name,
                            color: data[dictionaryIndex].color,
                            records: []
                        };

                        for (var jmdictIndex = 0; jmdictIndex < data[dictionaryIndex].jmdictRecords.length; jmdictIndex++) {
                            var jmdictRecord = data[dictionaryIndex].jmdictRecords[jmdictIndex];
                            
                            searchResult.records.push({
                                word: jmdictRecord.words.length ? jmdictRecord.words[0] : '',
                                otherForms: data[dictionaryIndex].jmdictRecords[jmdictIndex].words,
                                definitions: data[dictionaryIndex].jmdictRecords[jmdictIndex].definitions,
                            });                            
                        }

                        if (searchResult.records.length) {
                            this.dictionarySearchResultsFound = true;
                        }

                        this.searchResults.push(searchResult);
                    } else {
                        let searchResult = {
                            dictionary: data[dictionaryIndex].name,
                            color: data[dictionaryIndex].color,
                            records: []
                        };

                        console.log('wtf', data[dictionaryIndex])
                        for (var recordIndex = 0; recordIndex < data[dictionaryIndex].records.length; recordIndex++) {
                            searchResult.records.push({
                                word: data[dictionaryIndex].records[recordIndex].word,
                                definitions: data[dictionaryIndex].records[recordIndex].definitions,
                            });                            
                        }

                        if (searchResult.records.length) {
                            this.dictionarySearchResultsFound = true;
                        }
                        
                        this.searchResults.push(searchResult);
                    }
                }
            }
        }
    }
</script>
