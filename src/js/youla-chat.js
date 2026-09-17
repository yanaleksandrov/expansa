document.addEventListener('youla:init', () => {
    const THINKING_STATUSES = [
        'Думаю над ответом…',
        'Изучаю контекст диалога…',
        'Подбираю формулировку…',
        'Почти готово…',
    ];

    Youla.data('chat', () => {
        const messagesByProcess = {
            1: [
                { role: 'user', text: 'Помоги настроить импорт каталога товаров из CSV.' },
                { role: 'assistant', text: 'Конечно! Пришлите файл или ссылку на него, и уточните, какие поля нужно сопоставить.' },
            ],
            2: [
                { role: 'user', text: 'Какие мета-теги нужно проверить в первую очередь?' },
                { role: 'assistant', text: 'Начните с title, description и canonical — это чаще всего влияет на индексацию.' },
            ],
            3: [],
            4: [],
        };

        return {
            activeId: 1,
            draft: '',
            thinking: false,
            thinkingStatus: THINKING_STATUSES[0],
            statusTimer: null,
            replyTimer: null,
            processes: [
                { id: 1, title: 'Импорт каталога товаров', time: 'Сегодня, 12:04' },
                { id: 2, title: 'Настройка SEO для блога', time: 'Вчера, 18:41' },
                { id: 3, title: 'Миграция пользователей', time: '2 дня назад' },
                { id: 4, title: 'Настройка email-рассылок', time: '4 дня назад' },
            ],
            messagesByProcess,
            activeMessages: messagesByProcess[1],
            select(id) {
                this.stop();
                this.activeId = id;
                if (!this.messagesByProcess[id]) {
                    this.messagesByProcess[id] = [];
                }
                this.activeMessages = this.messagesByProcess[id];
            },
            newProcess() {
                this.stop();
                const id = Date.now();
                this.processes.unshift({ id, title: 'Новый процесс', time: 'Только что' });
                this.messagesByProcess[id] = [];
                this.select(id);
                this.draft = '';
            },
            stop() {
                clearInterval(this.statusTimer);
                clearTimeout(this.replyTimer);
                this.statusTimer = null;
                this.replyTimer = null;
                this.thinking = false;
            },
            send() {
                const text = this.draft.trim();
                if (!text || this.thinking) {
                    return;
                }
                this.activeMessages.push({ role: 'user', text });
                this.draft = '';
                this.thinking = true;
                let step = 0;
                this.thinkingStatus = THINKING_STATUSES[0];
                this.statusTimer = setInterval(() => {
                    step = (step + 1) % THINKING_STATUSES.length;
                    this.thinkingStatus = THINKING_STATUSES[step];
                }, 900);
                const messages = this.activeMessages;
                this.replyTimer = setTimeout(() => {
                    clearInterval(this.statusTimer);
                    this.statusTimer = null;
                    this.replyTimer = null;
                    this.thinking = false;
                    messages.push({
                        role: 'assistant',
                        text: 'Это демонстрационная заглушка интерфейса — подключение к ассистенту ещё не выполнено.',
                    });
                }, 3200);
            },

            // -- u-bind view bindings ------------------------------------------------
            // The template only wires elements up via u-bind; every reactive class,
            // visibility, text and event handler is produced here.
            newProcessButton: {
                '@click'() {
                    this.newProcess();
                },
            },
            processItem(process) {
                return {
                    ':class'() {
                        return process.id === this.activeId && 'active';
                    },
                    '@click'() {
                        this.select(process.id);
                    },
                };
            },
            processTitle(process) {
                return {
                    'u-text'() {
                        return process.title;
                    },
                };
            },
            processTime(process) {
                return {
                    'u-text'() {
                        return process.time;
                    },
                };
            },
            emptyState: {
                'u-show'() {
                    return this.activeMessages.length === 0;
                },
            },
            messageItem(message) {
                return {
                    ':class'() {
                        return `chat-message--${message.role}`;
                    },
                };
            },
            messageAvatar(message) {
                return {
                    ':class'() {
                        return message.role === 'user' ? 'ph ph-user' : 'ph ph-sparkle';
                    },
                };
            },
            messageText(message) {
                return {
                    'u-text'() {
                        return message.text;
                    },
                };
            },
            progressBar: {
                'u-show'() {
                    return this.thinking;
                },
            },
            thinkingIndicator: {
                'u-show'() {
                    return this.thinking;
                },
            },
            thinkingText: {
                'u-text'() {
                    return this.thinkingStatus;
                },
            },
            composerInput: {
                'u-prop': 'draft',
                ':disabled'() {
                    return this.thinking;
                },
                '@keydown.enter.prevent'() {
                    this.send();
                },
            },
            sendButton: {
                'u-show'() {
                    return !this.thinking;
                },
                ':disabled'() {
                    return !this.draft.trim();
                },
                '@click'() {
                    this.send();
                },
            },
            stopButton: {
                'u-show'() {
                    return this.thinking;
                },
                '@click'() {
                    this.stop();
                },
            },
        };
    });
});
