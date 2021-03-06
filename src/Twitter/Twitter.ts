import {
  ISlideContext,
  IPublicSlide,
  SlideModule,
  VueInstance, IAssetsStorageAbility, IAssetDownload
} from "dynamicscreen-sdk-js";

import Tweet from "../Components/Tweet";
import TweetAttachments from "../Components/TweetAttachments";

export default class TwitterSlideModule extends SlideModule {
    async onReady() {
      await this.context.assetsStorage().then(async (ability: IAssetsStorageAbility) => {
        // await ability.downloadAndGet(this.context.slide.data.logo);
        await ability.downloadAndGet(this.context.slide.data.avatar);

        if (!!this.context.slide.data.media_url) {
          await ability.downloadAndGet(this.context.slide.data.media_url);
        }
      });

      return true
    };

    setup(props: Record<string, any>, vue: VueInstance, context: ISlideContext) {
      const { h, reactive, computed, ref } = vue;
      const slide = reactive(this.context.slide) as IPublicSlide

      // const logo = ref(slide.data.logo);
      const text = ref(slide.data.text);
      const userPicture = ref(slide.data.avatar);
      const userName = ref(slide.data.name);
      const publicationDate = ref(slide.data.created_at);

      // const isTweetWithAttachment = computed(() => {
      //     return !!slide.data.attachmentUrl;
      // });
      // const tweetAttachment = isTweetWithAttachment.value ? ref(slide.data.attachmentUrl) : null;

      const isTweetWithAttachment = computed(() => {
        return !!slide.data.media_url;
      })
      const tweetAttachment = isTweetWithAttachment.value ? ref(slide.data.media_url) : null;

      this.context.onPrepare(async () => {
        isTweetWithAttachment.value && await this.context.assetsStorage().then(async (ability: IAssetsStorageAbility) => {
          tweetAttachment.value = await ability.getDisplayableAsset(slide.data.media_url).then((asset) => asset.displayableUrl());
        });

        await this.context.assetsStorage().then(async (ability: IAssetsStorageAbility) => {
          userPicture.value = await ability.getDisplayableAsset(slide.data.avatar).then((asset) => asset.displayableUrl());
        });

        // await this.context.assetsStorage().then(async (ability: IAssetsStorageAbility) => {
        //   logo.value = await ability.getDisplayableAsset(logo.value).then((asset) => asset.displayableUrl());
        // });
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

      return () => h("div", {
        class: "w-full h-full flex justify-center items-center"
      }, [
        //@ts-ignore
        !isTweetWithAttachment.value && h(Tweet, {
          text: text.value,
          userPicture: userPicture.value,
          userName: userName.value,
          publicationDate: publicationDate.value,
          class: "w-1/2"
          }),
        //@ts-ignore
        isTweetWithAttachment.value && h(TweetAttachments, {
          text: text.value,
          userPicture: userPicture.value,
          userName: userName.value,
          publicationDate: publicationDate.value,
          tweetAttachment: tweetAttachment.value,
          class: "w-full h-full"
        }),
        // h("i", {
        //   class: "w-16 h-16 absolute top-10 right-10 portrait:bottom-10 portrait:top-auto text-blue-400 " + logo
        // })
      ])
    }
}
