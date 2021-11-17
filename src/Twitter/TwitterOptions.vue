<template>
    <div>
    </div>
</template>

<script>
import {defineComponent} from "vue";
import i18next from "i18next";
import en from "../../languages/en.json";
import fr from "../../languages/fr.json";

export default defineComponent({
    name: "WeatherOptions",
    props: { context: {type: Object} },
    data() {
        return {};
    },
    mounted() {
        this.initI18n();
    },
    methods: {
        initI18n() {
            i18next.init({
                fallbackLng: 'en',
                lng: 'fr',
                resources: {
                    en: {translation: en},
                    fr: {translation: fr},
                },
                debug: true,
                ns: {
                    namespaces: ['translation'],
                    defaultNs: 'translation'
                }
            }, (err, t) => {
                if (err) return console.log('something went wrong loading translations', err);
            });
        },
    }
})

</script>

<style scoped>

</style>