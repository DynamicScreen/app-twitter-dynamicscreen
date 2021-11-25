import {
    BaseContext,
    AssetDownload,
    IAssetsStorageAbility,
    IGuardsManager,
    ISlideContext,
    IPublicSlide,
    SlideModule
} from "dynamicscreen-sdk-js";

import {computed, reactive, ref} from 'vue';

import { h } from "vue"
import Tweet from "../Components/Tweet";
import TweetAttachments from "../Components/TweetAttachments";

export default class TwitterSlideModule extends SlideModule {
    constructor(context: ISlideContext) {
        super(context);
    }

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

    // @ts-ignore
    setup(props, ctx) {
        const slide = reactive(props.slide) as IPublicSlide
        this.context = reactive(props.slide.context)

        const logo = ref(slide.data.logo);
        const isTweetWithAttachment = computed(() => {
            return !!slide.data.attachmentUrl;
        })
        const tweetAttachment = isTweetWithAttachment.value ? ref(slide.data.attachmentUrl) : null;
        const text = ref(slide.data.text);
        const userPicture = ref(slide.data.user.picture);
        const userName = ref(slide.data.user.name);
        const publicationDate = ref(slide.data.publicationDate);

        this.context.onPrepare(async () => {

        });

        this.context.onReplay(async () => {
        });

        this.context.onPlay(async () => {
          this.context.anime({
            targets: "#tweet",
            translateX: [-40, 0],
            opacity: [0, 1],
            duration: 600,
            easing: 'easeOutQuad'
          });
          this.context.anime({
            targets: "#user",
            translateX: [-40, 0],
            opacity: [0, 1],
            duration: 600,
            delay: 250,
            easing: 'easeOutQuad'
          });
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
              class: "w-1/2"
            }),
            isTweetWithAttachment.value && h(TweetAttachments, {
              text: text.value,
              userPicture: userPicture.value,
              userName: userName.value,
              publicationDate: publicationDate.value,
              tweetAttachment: tweetAttachment.value,
              class: "w-full h-full"
            }),
            h("i", {
              class: "w-16 h-16 absolute top-10 right-10 portrait:bottom-10 portrait:top-auto text-blue-400 " + logo
            })
          ])
    }
}
