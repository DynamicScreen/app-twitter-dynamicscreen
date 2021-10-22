import {defineComponent, h, ref, toRef} from "vue"
import {useTwitterContext} from "../Twitter";

export default defineComponent({
    setup() {
        console.log(useTwitterContext());
        const fieldUser = useTwitterContext();
        const userPicture = fieldUser.userPicture
        const userName = fieldUser.userName;
        const publicationDate = fieldUser.publicationDate;

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