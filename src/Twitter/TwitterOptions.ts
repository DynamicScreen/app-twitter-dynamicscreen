import {
  ISlideOptionsContext,
  SlideOptionsModule,
  VueInstance
} from "dynamicscreen-sdk-js";

export default class TwitterOptionsModule extends SlideOptionsModule {
  async onReady() {
    return true;
  };

  setup(props: Record<string, any>, vue: VueInstance, context: ISlideOptionsContext) {
    const { h } = vue;

    const update = this.context.update;
    const { Field, FieldsRow, TextInput, NumberInput } = this.context.components

    return () => [
      h(Field, { class: 'flex-1', label: this.t('modules.today.options.title') }, [
        h(TextInput, { min: 0, max: 100, default: 1, ...update.option("title") })
      ]),
      h(FieldsRow, {}, [
        h(Field, { class: 'flex-1', label: this.t('modules.today.options.username') }, [
          h(TextInput, { min: 0, max: 100, default: 1, ...update.option("username") })
        ]),
        h(Field, { class: 'flex-1', label: this.t('modules.today.options.tweets_count') }, [
          h(NumberInput, { min: 0, max: 100, default: 1, ...update.option("page") })
        ]),
      ])
    ]
  }
}
