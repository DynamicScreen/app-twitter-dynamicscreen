import {
  ISlideContext,
  IPublicSlide,
  SlideModule,
  VueInstance
} from "dynamicscreen-sdk-js";

export default class TwitterSlideModule extends SlideModule {
  async onReady() {
    return true;
  };

  setup(props: Record<string, any>, vue: VueInstance, context: ISlideContext) {
    const { h, reactive, computed, ref } = vue;

    return () =>
      h("div")
  }
}
