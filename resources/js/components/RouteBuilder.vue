<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <v-card>
                    <v-card-title>
                        Route Builder
                    </v-card-title>
                    <v-card-text>
                        Choose from the database of stations to build and submit a new route. Thank you!
                    </v-card-text>
                    <v-card-actions>
                        <v-autocomplete label="Type here..." :items="items" :filter="autocompleteFilter" filled rounded>
                            <!-- use template to stop .v-list-item__mask class being used, which was causing items 
                            with diacritics to be highlighted in full https://github.com/vuetifyjs/vuetify/pull/9618/files -->
                            <template v-slot:item="{ item }">
                                <span>{{ item.text }}</span>
                            </template>
                        </v-autocomplete>
                    </v-card-actions>
                </v-card>
            </div>
        </div>
    </div>
</template>

<script>
import deburr from "lodash/deburr";

export default {
    props: {
        stations: {
            type: Array
        }
    },
    computed: {
        items() {
            return this.stations.map((destination) => {
                return {
                    text: destination.name,
                    value: destination
                }
            })
        }
    },
    methods: {
        autocompleteFilter(item, queryText, itemText) {
            // same as default but adding _.deburr
            return (
                deburr(itemText)
                .toLocaleLowerCase()
                .indexOf(queryText.toLocaleLowerCase()) > -1
            );
        }
    }
}
</script>
