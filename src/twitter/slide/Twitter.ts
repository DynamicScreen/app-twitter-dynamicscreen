import {
    BaseContext,
    AssetDownload,
    IAssetsStorageAbility,
    IGuardsManager,
    ISlideContext,
    IPublicSlide,
    SlideModule
} from "dynamicscreen-sdk-js";

import {computed, inject, InjectionKey, onMounted, provide, reactive, Ref, ref, VNode} from 'vue';
import i18next from "i18next";

import { h } from "vue"
import Tweet from "./components/Tweet";
import TweetAttachments from "./components/TweetAttachments";

const en = require("../../../languages/en.json");
const fr = require("../../../languages/fr.json");

export default class TwitterSlideModule extends SlideModule {
    constructor(context: ISlideContext) {
        super(context);
    }

    trans(key: string) {
        return i18next.t(key);
    };

    async onReady() {
        return true;
    };

    onMounted() {
        console.log('onMounted')
    }

    //@ts-ignore
    onErrorTracked(err: Error, instance: Component, info: string) {
    }

    //@ts-ignore
    onRenderTriggered(e) {
    }

    //@ts-ignore
    onRenderTracked(e) {
    }

    onUpdated() {
    }

    initI18n() {
        i18next.init({
            fallbackLng: 'en',
            lng: 'fr',
            resources: {
                en: { translation: en },
                fr: { translation: fr },
            },
            debug: true,
        }, (err, t) => {
            if (err) return console.log('something went wrong loading translations', err);
        });
    };

    // @ts-ignore
    setup(props, ctx) {
        const slide = reactive(props.slide) as IPublicSlide
        this.context = reactive(props.slide.context)

        const logo = ref(slide.data.logo);
        const isTweetWithAttachment = computed(() => {
            return !!slide.data.attachment;
        })
        const tweetAttachment = isTweetWithAttachment.value ? ref(slide.data.url) : null;
        const text = ref(slide.data.text);
        const userPicture = ref(slide.data.user.picture);
        const userName = ref(slide.data.user.name);
        const publicationDate = ref(slide.data.publicationDate);

        this.context.onPrepare(async () => {

        });

        this.context.onReplay(async () => {
        });

        this.context.onPlay(async () => {

        });

        // this.context.onPause(async () => {
        //   console.log('Message: onPause')
        // });

        this.context.onEnded(async () => {
        });

        return () =>
            h("div", {
                class: "w-full h-full flex justify-center items-center"
            }, [
                !isTweetWithAttachment.value && h(Tweet, {
                    text: text.value,
                    userPicture: userPicture.value,
                    userName: userName.value,
                    publicationDate: publicationDate.value,
                    class: "w-1/2 font-medium"
                }),
                isTweetWithAttachment.value && h(TweetAttachments, {
                    text: text.value,
                    userPicture: userPicture.value,
                    userName: userName.value,
                    publicationDate: publicationDate.value,
                    tweetAttachment: tweetAttachment.value,
                    class: "w-full h-full"
                }),
                h("div", {
                    class: "rounded-full bg-contain bg-no-repeat bg-center w-16 h-16 absolute top-10 right-10",
                    style: {
                        backgroundImage: "url(" + logo.value + ")"
                    }
                })
            ])
    }
}