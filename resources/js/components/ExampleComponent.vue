<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <v-card>
                    <v-card-title>
                        Example Component
                    </v-card-title>
                    <v-card-text>
                        I'm a Vuetify example component
                    </v-card-text>
                    <v-card-actions>
                        <v-autocomplete label="Type here..." :items="items" filled rounded></v-autocomplete>
                    </v-card-actions>
                </v-card>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data: () => ({
            items: ['foo', 'bar', 'fizz', 'buzz']
        }),
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
