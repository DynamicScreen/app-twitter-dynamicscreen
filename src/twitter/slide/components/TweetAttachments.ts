import {defineComponent, h, toRef} from "vue";

import User from "./User";

export default defineComponent({
    props: {
        text: { type: String, required: true },
        tweetAttachment: { type: String, required: true },
        userPicture: { type: String, required: true },
        userName: { type: String, required: true },
        publicationDate: { type: String, required: true }
    },
    setup(props) {
        const text = toRef(props, "text");
        const tweetAttachment = toRef(props, "tweetAttachment");
        const userPicture = toRef(props, "userPicture");
        const userName = toRef(props, "userName");
        const publicationDate = toRef(props, "publicationDate");

        return () =>
            h("div", {
                class: "container flex flex-row"
            }, [
                h("div", {
                    class: "bloc-left w-1/2 flex h-full bg-blue-400 flex-row items-center"
                }, [
                    h("div", {
                        class: "h-2/3 w-full bg-contain bg-no-repeat bg-center",
                        style: {
                            backgroundImage: "url(" + tweetAttachment.value + ")"
                        }
                    })
                ]),
                h("div", {
                    class: "bloc-right w-1/2 h-full flex items-center justify-center"
                }, [
                    h("div", {
                        class: "w-2/3 h-2/3 text-2xl font-semibold flex justify-center flex-col space-y-10"
                    }, [
                        h("div", {
                        }, text.value),
                        h(User, {
                            userPicture: userPicture.value,
                            userName: userName.value,
                            publicationDate: publicationDate.value,
                        })
                    ])
                ])
            ])
    }

})