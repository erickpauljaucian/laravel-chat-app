<template>
    <div class="card chat-box">
        <div class="card-header">
            <b :class="{'text-danger':session.isBlocked}">
                {{ friend.name }} <span v-if="isTyping">is typing . . .</span>
                <span v-if="session.isBlocked">(Blocked)</span>
            </b>

            <!-- Close Button -->
            <a href="" @click.prevent="close">
                <i class="fa fa-times float-right" aria-hidden="true"></i>
            </a>
            <!-- Close Button Ends -->

            <!-- Options -->
            <div class="dropdown float-end">
                <a href="" type="button" id="dropdownMenuButton1"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-ellipsis-v mr-4" aria-hidden="true"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li v-if="session.isBlocked && canUnblock"><a class="dropdown-item" href="#"
                                                                  @click.prevent="unblock">Unblock</a></li>
                    <li v-if="!session.isBlocked"><a class="dropdown-item" href="#" @click.prevent="block">Block</a>
                    </li>
                    <li><a class="dropdown-item" href="#" @click.prevent="clear">Clear Chat</a></li>
                </ul>
            </div>
            <!-- Options Ends -->
        </div>
        <div class="card-body" v-chat-scroll @click="showPicker = false">
            <p class="card-text" v-for="chat in chats" :key="chat.id" :class="{'text-end':chat.type==0,
            'text-success': chat.read_at != null}">
                {{ chat.message }}
                <br>
                <span style="font-size:8px">{{ chat.read_at }}</span>
            </p>
        </div>
        <div class="card-footer">
            <div class="form-group">
                <emoji
                    set="twitter"
                    :emoji="{ id: randomEmoji }"
                    :size="22"
                    @click="showPicker = !showPicker"
                    class="icons emoji"
                />
                <picker
                    @select="pushEmoji"
                    set="twitter"
                    class="emoji"
                    v-show="showPicker"
                    @click="showPicker = false"
                />
                <form @submit.prevent="send">
                    <input type="text" class="form-control" id="inputMessage" v-model="message" placeholder="Write your message here"
                           :disabled="session.isBlocked">
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import {Emoji, Picker} from 'emoji-mart-vue'

export default {
    props: ['friend'],
    components: {
        Picker, Emoji
    },
    data() {
        return {
            chats: [],
            message: "",
            isTyping: false,
            showPicker: false,
            randomEmoji: "grinning",
        }
    },
    computed: {
        session() {
            return this.friend.session;
        },
        canUnblock() {
            return this.session.blockedBy == auth.id;
        }
    },
    watch: {
        message(value) {
            if (value) {
                Echo.private(`Chat.${this.friend.session.id}`)
                    .whisper('typing', {
                        name: auth.name
                    });
            }
        }
    },
    created() {
        this.read();

        this.getAllMessages();

        Echo.private(`Chat.${this.friend.session.id}`).listen('PrivateChatEvent', (e) => {
            this.friend.session.open ? this.read() : "";
            this.chats.push({
                message: e.content,
                type: 1,
                sent_at: 'Just now'
            });
        });

        Echo.private(`Chat.${this.friend.session.id}`).listen('MsgReadEvent',
            e => this.chats.forEach(chat => chat.id == e.chat.id ? (chat.read_at = e.chat.read_at) : "")
        );

        Echo.private(`Chat.${this.friend.session.id}`).listen('BlockEvent',
            e => (this.session.isBlocked = e.isBlocked)
        );

        Echo.private(`Chat.${this.friend.session.id}`).listenForWhisper('typing',
            e => {
                this.isTyping = true;
                setTimeout(() => {
                    this.isTyping = false;
                }, 1000);
            }
        );
    },
    mounted() {
        console.log('MessageComponent mounted.')
    },
    methods: {
        send() {
            this.showPicker = false;
            let $message = this.message;
            if ($message && !this.friend.session.isBlocked) {
                this.pushToChats($message)
                let requestBody = {
                    message: $message,
                    to_user: this.friend.id
                }
                axios.post(`/session/${this.friend.session.id}/send`, requestBody)
                    .then(res => this.chats[this.chats.length - 1].id = res.data);
                this.message = null;
            }
        },
        pushToChats(message) {
            this.chats.push({
                message: message,
                type: 0,
                read_at: null,
                sent_at: 'Just now'
            });
        },
        close() {
            this.$emit('close')
        },
        clear() {
            axios.post(`session/${this.friend.session.id}/clear`)
                .then(res => this.chats = []);
        },
        block() {
            this.friend.session.isBlocked = true;
            axios.post(`session/${this.friend.session.id}/block`)
                .then(res => this.session.blockedBy = auth.id);
        },
        unblock() {
            this.friend.session.isBlocked = false;
            axios.post(`session/${this.friend.session.id}/unblock`)
                .then(res => this.session.blockedBy = null);
        },
        getAllMessages() {
            axios.get(`/session/${this.friend.session.id}/chats`)
                .then(res => (this.chats = res.data.data));
        },
        read() {
            axios.post(`/session/${this.friend.session.id}/read`)
        },
        pushEmoji(emoji) {
            this.message = this.message == null ? "" : this.message;
            this.message += emoji.native;
            this.showPicker = !this.showPicker;
        }
    }
}
</script>
<style scoped>
.chat-box {
    height: 400px;
}

.card-body {
    overflow-y: scroll;
}

.mr-4 {
    margin-right: 10px;
}
#inputMessage {
    width: 95%;
}
.emoji {
    position: absolute !important;
    right: 10px;
    padding-top: 10px;
}
</style>
