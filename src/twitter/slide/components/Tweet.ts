import {defineComponent, h, toRef} from "vue"

import User from "./User"

export default defineComponent({
    props: {
        text:  { type: String, required: true}
    },
    setup(props) {
        const text = toRef(props, "text")

        return () =>
            h("div", {
                class: "container flex flex-col space-y-10"
            }, [
                h("div", {
                    class: "text-3xl font-semibold text-gray-800"
                }, text.value),
                h(User)
            ])
    }
})