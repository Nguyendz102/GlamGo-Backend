$(document).ready(function () {
    let startChartBtn = $(".wrapper-contact-realtime #startChart");
    let btnChat = $(".wrapper-contact-realtime #btnChat");
    let startBox = $(".wrapper-contact-realtime .start-chat");
    let contentBox = $(".wrapper-contact-realtime .content-chat");
    let sessionId = localStorage.getItem("sessionId");
    let now = new Date();

    if (localStorage.getItem("fist-time")) {
        startBox.css("display", "none");
        contentBox.css("display", "block");
    } else {
        contentBox.css("display", "none");
        startBox.css("display", "block");
    }

    if (
        localStorage.getItem("fist-time") &&
        localStorage.getItem("sessionId")
    ) {
        let sessionId = localStorage.getItem("sessionId");

        if (!sessionId) return;

        axios
            .get("/api/shop-customer/pull-message/" + sessionId)
            .then((res) => {
                let data = res.data;

                if (!Array.isArray(data) || data.length === 0) return;

                let messageHTML = "";

                data.forEach((messageItem) => {
                    if (messageItem.is_admin) {
                        messageHTML += `
                        <div class="message-customer">
                            <span class="message">${messageItem.message}</span>
                            <span class="d-block time-send">${formatDateTime(
                                messageItem.created_at
                            )}</span>
                        </div>`;
                    } else {
                        messageHTML += `
                        <div class="message-admin">
                            <span class="message">${messageItem.message}</span>
                            <span class="d-block time-send">${formatDateTime(
                                messageItem.created_at
                            )}</span>
                        </div>`;
                    }
                });
                $(
                    ".wrapper-contact-realtime .content-chat .list-message"
                ).append(messageHTML);
            })
            .catch((error) => {
                console.error("Lỗi khi lấy tin nhắn:", error);
            });
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);

        return new Intl.DateTimeFormat("vi-VN", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            hour12: true,
        }).format(date);
    }

    startChartBtn.click(function () {
        let name = $(".wrapper-contact-realtime #chat-name").val();
        let email = $(".wrapper-contact-realtime #chat-email").val();
        let content = $(".wrapper-contact-realtime #chat-content").val();

        if (name && content) {
            let data = {
                username: name,
                message: content,
                email,
                ssID: sessionId,
            };

            console.log(now.toLocaleString("vi-VN"));

            const formattedTime = formatDateTime(now.toISOString());

            $(".wrapper-contact-realtime .content-chat .list-message").append(`
    <div class="message-admin">
        <span class="message">${content}</span>
        <span class="d-block time-send">${formattedTime}</span>
    </div>    
`);

            $(".wrapper-contact-realtime #chat-content").val("");
            axios
                .post("/message-customer", data)
                .then((response) => {
                    if (response.data.messc === "Ok") {
                        localStorage.setItem("fist-time", true);
                        localStorage.setItem("user_shop_global", name);
                        startBox.css("display", "none");
                        contentBox.css("display", "block");
                    }
                })
                .catch((error) => console.log(error));
        } else {
            if (!name) {
                $(".wrapper-contact-realtime .err-name").text(
                    "Please enter your full name?"
                );
            }
            if (!content) {
                $(".wrapper-contact-realtime .err-content").text(
                    "Please enter the content you need advice on?"
                );
            }
        }
    });

    btnChat.click(function () {
        let inputMessage = $(
            ".wrapper-contact-realtime .content-chat #input-message"
        ).val();
        if (inputMessage) {
            const formattedTime = formatDateTime(now.toISOString());

            $(".wrapper-contact-realtime .content-chat .list-message").append(`
    <div class="message-admin">
        <span class="message">${inputMessage}</span>
        <span class="d-block time-send">${formattedTime}</span>
    </div>    
`);

            $(".wrapper-contact-realtime .content-chat #input-message").val("");

            axios
                .post("/message-customer", {
                    message: inputMessage,
                    ssID: sessionId,
                    username: localStorage.getItem("user_shop_global"),
                })
                .then((response) => {
                    if (response.data.messc === "Ok") {
                        localStorage.setItem("fist-time", true);
                        startBox.css("display", "none");
                        contentBox.css("display", "block");
                    }
                })
                .catch((error) => console.log(error));
        }
    });

    // sự kiện enter

    document
        .getElementById("input-message")
        .addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                let inputMessage = $(
                    ".wrapper-contact-realtime .content-chat #input-message"
                ).val();
                if (inputMessage) {
                    const formattedTime = formatDateTime(now.toISOString());

                    $(".wrapper-contact-realtime .content-chat .list-message")
                        .append(`
        <div class="message-admin">
            <span class="message">${inputMessage}</span>
            <span class="d-block time-send">${formattedTime}</span>
        </div>    
    `);

                    $(
                        ".wrapper-contact-realtime .content-chat #input-message"
                    ).val("");

                    axios
                        .post("/message-customer", {
                            message: inputMessage,
                            ssID: sessionId,
                            username: localStorage.getItem("user_shop_global"),
                        })
                        .then((response) => {
                            if (response.data.messc === "Ok") {
                                localStorage.setItem("fist-time", true);
                                startBox.css("display", "none");
                                contentBox.css("display", "block");
                            }
                        })
                        .catch((error) => console.log(error));
                }
            }
        });

    // enter 2

    document
        .getElementById("chat-content")
        .addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                let name = $(".wrapper-contact-realtime #chat-name").val();
                let email = $(".wrapper-contact-realtime #chat-email").val();
                let content = $(
                    ".wrapper-contact-realtime #chat-content"
                ).val();

                if (name && content) {
                    let data = {
                        username: name,
                        message: content,
                        email,
                        ssID: sessionId,
                    };

                    console.log(now.toLocaleString("vi-VN"));

                    const formattedTime = formatDateTime(now.toISOString());

                    $(".wrapper-contact-realtime .content-chat .list-message")
                        .append(`
            <div class="message-admin">
                <span class="message">${content}</span>
                <span class="d-block time-send">${formattedTime}</span>
            </div>    
        `);

                    $(".wrapper-contact-realtime #chat-content").val("");
                    axios
                        .post("/message-customer", data)
                        .then((response) => {
                            if (response.data.messc === "Ok") {
                                localStorage.setItem("fist-time", true);
                                localStorage.setItem("user_shop_global", name);
                                startBox.css("display", "none");
                                contentBox.css("display", "block");
                            }
                        })
                        .catch((error) => console.log(error));
                } else {
                    if (!name) {
                        $(".wrapper-contact-realtime .err-name").text(
                            "Please enter your full name?"
                        );
                    }
                    if (!content) {
                        $(".wrapper-contact-realtime .err-content").text(
                            "Please enter the content you need advice on?"
                        );
                    }
                }
            }
        });
});
