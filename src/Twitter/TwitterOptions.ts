import {
  BaseContext,
  AssetDownload,
  IAssetsStorageAbility,
  IGuardsManager,
  ISlideContext,
  IPublicSlide,
  SlideModule,
  SlideUpdateFunctions
} from "dynamicscreen-sdk-js";

import i18next from "i18next";

const en = require("../../languages/en.json");
const fr = require("../../languages/fr.json");

export default class TwitterOptionsModule extends SlideModule {
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
    console.log('onMounted Twitter OPTIONS')
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

  };

// @ts-ignore
  setup(props, ctx, update: SlideUpdateFunctions, OptionsContext) {
    const { h } = ctx;

    const { Field, FieldsRow, TextInput, NumberInput } = OptionsContext.components

    return () => [
      h(Field, { class: 'flex-1', label: "Titre" }, [
        h(TextInput, { min: 0, max: 100, default: 1, ...update.option("title") })
      ]),
      h(FieldsRow, {}, [
        h(Field, { class: 'flex-1', label: "Nom d'utilisateur" }, [
          h(TextInput, { min: 0, max: 100, default: 1, ...update.option("username") })
        ]),
        h(Field, { class: 'flex-1', label: "Nombre de tweets à afficher" }, [
          h(NumberInput, { min: 0, max: 100, default: 1, ...update.option("page") })
        ]),
      ])
    ]
  }
}
