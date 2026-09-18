document.addEventListener('youla:init', () => {
    const THINKING_STATUSES = [ 'Думаю над ответом…', 'Изучаю контекст диалога…', 'Подбираю формулировку…', 'Почти готово…' ];
    Youla.data('chat', () => {
        const messagesByProcess = {
            1: [ {
                role: 'user',
                text: 'Помоги настроить импорт каталога товаров из CSV.',
                time: '12:01'
            }, {
                role: 'assistant',
                text: 'Конечно! Пришлите файл или ссылку на него, и уточните, какие поля нужно сопоставить.',
                time: '12:04'
            } ],
            2: [ {
                role: 'user',
                text: 'Какие мета-теги нужно проверить в первую очередь?',
                time: '18:38'
            }, {
                role: 'assistant',
                text: 'Начните с title, description и canonical — это чаще всего влияет на индексацию.',
                time: '18:41'
            } ],
            3: [],
            4: []
        };
        const STATUS_META = {
            completed: {
                icon: 'ph ph-check',
                color: 'var(--expansa-success)'
            },
            process: {
                icon: '',
                color: 'var(--expansa-primary)'
            },
            paused: {
                icon: 'ph ph-pause',
                color: 'var(--expansa-warning)'
            }
        };
        return {
            activeId: 1,
            draft: '',
            thinking: false,
            thinkingStatus: THINKING_STATUSES[0],
            statusTimer: null,
            replyTimer: null,
            renamingId: null,
            renameDraft: '',
            processes: [ {
                id: 1,
                title: 'Импорт каталога товаров',
                status: 'process',
                progress: 62,
                archived: false
            }, {
                id: 2,
                title: 'Настройка SEO для блога',
                status: 'paused',
                progress: 40,
                archived: false
            }, {
                id: 3,
                title: 'Миграция пользователей',
                status: 'completed',
                progress: 100,
                archived: false
            }, {
                id: 4,
                title: 'Настройка email-рассылок',
                status: 'process',
                progress: 15,
                archived: false
            } ],
            messagesByProcess,
            activeMessages: messagesByProcess[1],
            get visibleProcesses() {
                return this.processes.filter(process => !process.archived);
            },
            statusMeta(status) {
                return STATUS_META[status] ?? {
                    icon: 'ph ph-circle',
                    color: 'var(--expansa-text-muted)'
                };
            },
            formatTime(date = new Date) {
                return date.toLocaleTimeString('ru-RU', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },
            select(id) {
                this.stop();
                this.activeId = id;
                if (!this.messagesByProcess[id]) {
                    this.messagesByProcess[id] = [];
                }
                this.activeMessages = this.messagesByProcess[id];
            },
            selectFirstVisibleOrClear() {
                const next = this.visibleProcesses[0];
                if (next) {
                    this.select(next.id);
                    return;
                }
                this.stop();
                this.activeId = null;
                this.activeMessages = [];
            },
            newProcess() {
                this.stop();
                const id = Date.now();
                this.processes.unshift({
                    id,
                    title: 'Новый процесс',
                    status: 'process',
                    progress: 0,
                    archived: false
                });
                this.messagesByProcess[id] = [];
                this.select(id);
                this.draft = '';
            },
            startRename(process) {
                this.renamingId = process.id;
                this.renameDraft = process.title;
            },
            saveRename(process) {
                if (this.renamingId !== process.id) {
                    return;
                }
                const title = this.renameDraft.trim();
                if (title) {
                    process.title = title;
                }
                this.renamingId = null;
                this.renameDraft = '';
            },
            cancelRename() {
                this.renamingId = null;
                this.renameDraft = '';
            },
            archiveProcess(process) {
                process.archived = true;
                if (this.renamingId === process.id) {
                    this.cancelRename();
                }
                if (process.id === this.activeId) {
                    this.selectFirstVisibleOrClear();
                }
            },
            deleteProcess(process) {
                this.processes = this.processes.filter(p => p.id !== process.id);
                delete this.messagesByProcess[process.id];
                if (this.renamingId === process.id) {
                    this.cancelRename();
                }
                if (process.id === this.activeId) {
                    this.selectFirstVisibleOrClear();
                }
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
                this.activeMessages.push({
                    role: 'user',
                    text,
                    time: this.formatTime()
                });
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
                        time: this.formatTime()
                    });
                }, 3200);
            },
            newProcessButton: {
                '@click'() {
                    this.newProcess();
                }
            },
            processRow(process) {
                return {
                    ':class'() {
                        return process.id === this.activeId && 'active';
                    }
                };
            },
            processSelectButton(process) {
                return {
                    'u-show'() {
                        return this.renamingId !== process.id;
                    },
                    '@click'() {
                        this.select(process.id);
                    }
                };
            },
            processTitle(process) {
                return {
                    'u-text'() {
                        return process.title;
                    }
                };
            },
            processRenameInput(process) {
                return {
                    'u-show'() {
                        return this.renamingId === process.id;
                    },
                    'u-prop': 'renameDraft',
                    '@keydown.enter.prevent'() {
                        this.saveRename(process);
                    },
                    '@keydown.escape.prevent'() {
                        this.cancelRename();
                    },
                    '@blur'() {
                        this.saveRename(process);
                    }
                };
            },
            processStatusLabel(process) {
                return {
                    'u-text'() {
                        return `${process.progress}%`;
                    }
                };
            },
            processStatusBadge(process) {
                return {
                    ':style'() {
                        return {
                            backgroundColor: this.statusMeta(process.status).color
                        };
                    },
                    ':class'() {
                        const isAnswering = process.status === 'process' && process.id === this.activeId && this.thinking;
                        return isAnswering && 'chat-status-pulse';
                    }
                };
            },
            processStatusGlyph(process) {
                return {
                    ':class'() {
                        return this.statusMeta(process.status).icon;
                    }
                };
            },
            renameProcessButton(process) {
                return {
                    '@click'() {
                        this.startRename(process);
                        this.$el.closest('details').removeAttribute('open');
                    }
                };
            },
            archiveProcessButton(process) {
                return {
                    '@click'() {
                        this.archiveProcess(process);
                        this.$el.closest('details').removeAttribute('open');
                    }
                };
            },
            deleteProcessButton(process) {
                return {
                    '@click'() {
                        this.deleteProcess(process);
                        this.$el.closest('details').removeAttribute('open');
                    }
                };
            },
            emptyState: {
                'u-show'() {
                    return this.activeMessages.length === 0;
                }
            },
            messageItem(message) {
                return {
                    ':class'() {
                        return `chat-message--${message.role}`;
                    }
                };
            },
            messageAvatar(message) {
                return {
                    ':class'() {
                        return message.role === 'user' ? 'ph ph-user' : 'ph ph-sparkle';
                    }
                };
            },
            messageText(message) {
                return {
                    'u-text'() {
                        return message.text;
                    }
                };
            },
            messageTime(message) {
                return {
                    'u-text'() {
                        return message.time;
                    }
                };
            },
            progressBar: {
                'u-show'() {
                    return this.thinking;
                }
            },
            thinkingIndicator: {
                'u-show'() {
                    return this.thinking;
                }
            },
            thinkingText: {
                'u-text'() {
                    return this.thinkingStatus;
                }
            },
            composerInput: {
                'u-prop': 'draft',
                ':disabled'() {
                    return this.thinking;
                },
                '@keydown.enter.prevent'() {
                    this.send();
                }
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
                }
            },
            stopButton: {
                'u-show'() {
                    return this.thinking;
                },
                '@click'() {
                    this.stop();
                }
            }
        };
    });
});