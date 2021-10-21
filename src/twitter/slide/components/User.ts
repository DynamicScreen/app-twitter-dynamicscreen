import {defineComponent, h, ref, toRef} from "vue"
import {useFieldUser} from "../Twitter";

export default defineComponent({
    setup() {
        console.log(useFieldUser());
        const fieldUser = useFieldUser();
        const userPicture = ref(fieldUser.userPicture)
        const userName = ref(fieldUser.userName);
        const publicationDate = ref(fieldUser.publicationDate);

        return () =>
            h("div", {
                class: "w-64 flex items-center space-x-5"
            }, [
                h("div", {
                    class: "rounded-full w-16 h-16 bg-contain",
                    style: {
                        backgroundImage: "url(" + userPicture.value + ")"
                    }
                }),
                h("div", {
                    class: "flex-col"
                }, [
                    h("div", {
                        class: "font-semibold text-xl"
                    }, userName.value),
                    h("div", {
                        class: "text-gray-500 text-base font-light"
                    }, publicationDate.value)
                ])
            ])
    }

})