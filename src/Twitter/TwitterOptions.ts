import {
  ISlideOptionsContext,
  SlideOptionsModule,
  VueInstance
} from "dynamicscreen-sdk-js";
import debounce from "debounce";

export default class TwitterOptionsModule extends SlideOptionsModule {
  async onReady() {
    return true;
  };

  setup(props: Record<string, any>, vue: VueInstance, context: ISlideOptionsContext) {
    //@ts-ignore
    const { h, ref, reactive, watch, toRefs } = vue;

    const update = this.context.update;
    const { Field, FieldsRow, TextInput, NumberInput } = this.context.components

    let isAccountDataLoaded = ref(false);
    let usernames: any = reactive({});

    let { username } = toRefs(props.modelValue);

    watch(() => username.value, debounce((searchTerm) => {
      if (searchTerm.length < 3) return []

      searchTwitterUser(searchTerm)
    }, 300))

    watch(() => username.value, (searchTerm) => {
      if (searchTerm.length < 3) usernames.value = []
    })

    const searchTwitterUser = (searchTerm: string) => {
      this.context.getAccountData?.("twitter-driver", "users", {
        onChange: (accountId: number | undefined) => {
          isAccountDataLoaded.value = accountId !== undefined;
          if (accountId === undefined) usernames.value = {};
        },
        extra: { q: searchTerm }
      })
        .value?.then((data: any) => {
        usernames.value = data.map((account) => {
          return { key: account?.screen_name, value: `${account?.name} (${account?.screen_name})` }
        });
        isAccountDataLoaded.value = true;
      }).catch((err) => {
        isAccountDataLoaded.value = false;
      });
    }

    return () => [
      h(Field, { class: 'flex-1', label: this.t('modules.twitter.options.title') }, [
        h(TextInput, { min: 0, max: 100, default: 1, ...update.option("title") })
      ]),
      h(FieldsRow, {}, [
        h(Field, { "onUpdate:modelValue": (val) => console.log('yolo', val), class: 'flex-1', label: this.t('modules.twitter.options.username') }, [
          h(TextInput, {
            min: 0,
            max: 100,
            options: usernames.value,
            placeholder: "Choisissez un compte tweeter",
            ...update.option("username"),
          })
        ]),
        // isAccountDataLoaded.value && h(Field, { class: 'flex-1', label: this.t('modules.twitter.options.username') }, [
        //   h(Select, {
        //     options: usernames.value,
        //     // options: [
        //     //   {
        //     //     name: 'aaa',
        //     //     screen_name: 'alo'
        //     //   },
        //     //   {
        //     //     name: 'dwdwd',
        //     //     screen_name: 'reeeeee'
        //     //   }
        //     // ],
        //     placeholder: "Choisissez un compte tweeter",
        //     keyProp: 'screen_name',
        //     valueProp: 'name',
        //     ...update.option(username.value),
        //     "onUpdate:modelValue": (val) => console.log(val, 'update'),
        //     "onUpdate:filterTerm": (val) => console.log(val, 'update filter')
        //   })
        // ]),
        h(Field, { class: 'flex-1', label: this.t('modules.twitter.options.tweets_count') }, [
          h(NumberInput, { min: 0, max: 100, default: 1, ...update.option("page") })
        ]),
      ])
    ]
  }
}
