document.addEventListener('youla:init', () => {
    // a running task is polled this often; steps finish every few seconds
    const POLL_INTERVAL = 1500;

    const STATUS_META = {
        ready: { icon: 'ph ph-check', color: 'var(--expansa-success)' },
        queued: { icon: '', color: 'var(--expansa-primary)' },
        running: { icon: '', color: 'var(--expansa-primary)' },
        questions: { icon: 'ph ph-question', color: 'var(--expansa-warning)' },
        invalid: { icon: 'ph ph-warning', color: 'var(--expansa-warning)' },
        missing_extensions: { icon: 'ph ph-warning', color: 'var(--expansa-warning)' },
        failed: { icon: 'ph ph-x', color: 'var(--expansa-danger)' },
        cancelled: { icon: 'ph ph-stop', color: 'var(--expansa-text-muted)' },
    };

    const readCookie = (name) => {
        const match = document.cookie.match('(?:^|; )' + name + '=([^;]*)');
        return match ? decodeURIComponent(match[1]) : null;
    };

    Youla.data('chat', () => ({
        labels: {},
        tasks: [],
        task: null,
        messages: [],
        activeId: null,
        draft: '',
        sending: false,
        configured: true,
        error: '',
        now: Date.now() / 1000,
        renamingId: null,
        renameDraft: '',
        pollTimer: null,
        tickTimer: null,
        snapshot: '',

        get isRunning() {
            return Boolean(this.task?.isPending);
        },
        get isAsking() {
            return this.task?.status === 'questions';
        },

        init(root) {
            try {
                this.labels = JSON.parse(root.dataset.labels || '{}');
            } catch {
                this.labels = {};
            }
            this.tickTimer = setInterval(() => {
                if (this.isRunning) {
                    this.now = Date.now() / 1000;
                }
            }, 1000);
            this.loadTasks().then(() => this.tasks[0] && this.select(this.tasks[0].id));
        },

        // $ajax aborts the previous request of the same element, and polling overlaps with sending
        async api(route, payload = {}) {
            const body = new FormData();
            Object.entries(payload).forEach(([key, value]) => body.append(key, value));
            const headers = {};
            const token = readCookie('x_csrf_token');
            if (token) {
                headers['X-CSRF-Token'] = token;
            }

            let response;
            try {
                response = await fetch((Youla.baseURL ?? '') + route, { method: 'POST', body, headers, credentials: 'same-origin' });
            } catch {
                throw new Error(this.labels.network);
            }

            const json = await response.json().catch(() => null);
            if (!response.ok) {
                throw new Error(json?.message || this.labels.network);
            }
            return json?.data ?? json;
        },

        async run(callback) {
            this.error = '';
            try {
                return await callback();
            } catch (error) {
                this.error = error.message;
                return null;
            }
        },

        async loadTasks() {
            const data = await this.run(() => this.api('ai/index'));
            if (data) {
                this.tasks = data.tasks;
                this.configured = data.configured;
            }
        },

        async select(id) {
            this.stopPolling();
            this.activeId = id;
            this.task = null;
            this.messages = [];
            this.snapshot = '';
            const task = await this.run(() => this.api('ai/get', { id }));
            if (task && this.activeId === id) {
                this.apply(task);
            }
        },

        // replacing the messages rebuilds the list, so it happens only when the task changed
        apply(task) {
            const snapshot = JSON.stringify(task.messages) + task.status;
            if (snapshot !== this.snapshot) {
                this.snapshot = snapshot;
                this.messages = task.messages;
                this.scrollDown();
            }
            this.task = task;
            this.now = Date.now() / 1000;

            const summary = { id: task.id, title: task.title, status: task.status, isPending: task.isPending, progress: task.progress };
            const index = this.tasks.findIndex((item) => item.id === task.id);
            if (index === -1) {
                this.tasks = [summary, ...this.tasks];
            } else {
                this.tasks = this.tasks.map((item) => (item.id === task.id ? { ...item, ...summary } : item));
            }

            if (task.isPending) {
                this.pollTimer = setTimeout(() => this.poll(task.id), POLL_INTERVAL);
            }
        },

        async poll(id) {
            this.pollTimer = null;
            if (this.activeId !== id) {
                return;
            }
            try {
                const task = await this.api('ai/get', { id });
                if (this.activeId === id) {
                    this.apply(task);
                }
            } catch (error) {
                // a failed poll is retried: the worker keeps running
                this.error = error.message;
                this.pollTimer = setTimeout(() => this.poll(id), POLL_INTERVAL * 2);
            }
        },

        stopPolling() {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        },

        scrollDown() {
            requestAnimationFrame(() => {
                const list = document.querySelector('.chat-messages');
                if (list) {
                    list.scrollTop = list.scrollHeight;
                }
            });
        },

        newProcess() {
            this.stopPolling();
            this.activeId = null;
            this.task = null;
            this.messages = [];
            this.snapshot = '';
            this.draft = '';
            this.error = '';
        },

        async send() {
            const text = this.draft.trim();
            if (!text || this.sending || this.isRunning) {
                return;
            }

            this.sending = true;
            this.draft = '';
            this.messages = [...this.messages, { role: 'user', text, time: Date.now() / 1000 }];
            this.scrollDown();

            const isAnswer = this.isAsking;
            if (!isAnswer) {
                this.stopPolling();
            }
            const task = await this.run(() => (isAnswer
                ? this.api('ai/clarify', { id: this.task.id, message: text })
                : this.api('ai/create', { message: text })));
            this.sending = false;

            if (!task) {
                // the request did not reach the queue: give the text back
                this.messages = this.messages.slice(0, -1);
                this.draft = text;
                return;
            }
            this.activeId = task.id;
            this.apply(task);
        },

        async stop() {
            if (!this.task) {
                return;
            }
            this.stopPolling();
            const task = await this.run(() => this.api('ai/cancel', { id: this.task.id }));
            if (task) {
                this.apply(task);
            }
        },

        startRename(task) {
            this.renamingId = task.id;
            this.renameDraft = task.title;
        },

        async saveRename(task) {
            if (this.renamingId !== task.id) {
                return;
            }
            const title = this.renameDraft.trim();
            this.cancelRename();
            if (!title || title === task.title) {
                return;
            }
            const summary = await this.run(() => this.api('ai/rename', { id: task.id, title }));
            if (summary) {
                this.tasks = this.tasks.map((item) => (item.id === task.id ? { ...item, title: summary.title } : item));
            }
        },

        cancelRename() {
            this.renamingId = null;
            this.renameDraft = '';
        },

        async remove(task, route) {
            if (!(await this.run(() => this.api(route, { id: task.id })))) {
                return;
            }
            this.tasks = this.tasks.filter((item) => item.id !== task.id);
            if (this.renamingId === task.id) {
                this.cancelRename();
            }
            if (task.id === this.activeId) {
                this.tasks[0] ? this.select(this.tasks[0].id) : this.newProcess();
            }
        },

        statusMeta(status) {
            return STATUS_META[status] ?? { icon: 'ph ph-circle', color: 'var(--expansa-text-muted)' };
        },

        formatTime(seconds) {
            return seconds ? new Date(seconds * 1000).toLocaleTimeString(document.documentElement.lang || undefined, { hour: '2-digit', minute: '2-digit' }) : '';
        },

        formatSeconds(seconds) {
            return (this.labels.seconds || '%s s').replace('%s', seconds);
        },

        // -- u-bind view bindings ------------------------------------------------
        // The template only wires elements up via u-bind; every reactive class,
        // visibility, text and event handler is produced here.
        newProcessButton: {
            '@click'() {
                this.newProcess();
            },
        },
        processRow(task) {
            return {
                ':class'() {
                    return task.id === this.activeId && 'active';
                },
            };
        },
        processSelectButton(task) {
            return {
                'u-show'() {
                    return this.renamingId !== task.id;
                },
                '@click'() {
                    this.select(task.id);
                },
            };
        },
        processTitle(task) {
            return {
                'u-text'() {
                    return task.title;
                },
            };
        },
        processRenameInput(task) {
            return {
                'u-show'() {
                    return this.renamingId === task.id;
                },
                'u-prop': 'renameDraft',
                '@keydown.enter.prevent'() {
                    this.saveRename(task);
                },
                '@keydown.escape.prevent'() {
                    this.cancelRename();
                },
                '@blur'() {
                    this.saveRename(task);
                },
            };
        },
        processStatusLabel(task) {
            return {
                'u-text'() {
                    return `${task.progress}%`;
                },
            };
        },
        processStatusBadge(task) {
            return {
                ':style'() {
                    return { backgroundColor: this.statusMeta(task.status).color };
                },
                ':class'() {
                    return task.isPending && 'chat-status-pulse';
                },
            };
        },
        processStatusGlyph(task) {
            return {
                ':class'() {
                    return this.statusMeta(task.status).icon;
                },
            };
        },
        renameProcessButton(task) {
            return {
                '@click'() {
                    this.startRename(task);
                    this.$el.closest('details').removeAttribute('open');
                },
            };
        },
        archiveProcessButton(task) {
            return {
                '@click'() {
                    this.remove(task, 'ai/archive');
                    this.$el.closest('details').removeAttribute('open');
                },
            };
        },
        deleteProcessButton(task) {
            return {
                '@click'() {
                    this.remove(task, 'ai/delete');
                    this.$el.closest('details').removeAttribute('open');
                },
            };
        },
        emptyState: {
            'u-show'() {
                return this.messages.length === 0;
            },
        },
        messageItem(message) {
            return {
                ':class'() {
                    const classes = [`chat-message--${message.role}`];
                    if (message.role === 'step' && message.failed) {
                        classes.push('chat-message--failed');
                    }
                    if (message.role === 'assistant') {
                        classes.push(`chat-message--${message.kind}`);
                    }
                    return classes.join(' ');
                },
            };
        },
        messageAvatar(message) {
            return {
                ':class'() {
                    if (message.role === 'user') {
                        return 'ph ph-user';
                    }
                    if (message.role === 'step') {
                        return message.running ? 'ph ph-circle-notch chat-spin' : message.failed ? 'ph ph-x' : 'ph ph-check';
                    }
                    return 'ph ph-sparkle';
                },
            };
        },
        stepHead(message) {
            return {
                'u-show'() {
                    return message.role === 'step';
                },
            };
        },
        stepLabel(message) {
            return {
                'u-text'() {
                    return message.label;
                },
            };
        },
        stepMeta(message) {
            return {
                'u-text'() {
                    if (message.role !== 'step') {
                        return '';
                    }
                    if (message.running) {
                        return this.formatSeconds(Math.max(0, Math.round(this.now - message.time)));
                    }
                    const parts = [];
                    if (message.duration !== null) {
                        parts.push(this.formatSeconds(message.duration));
                    }
                    if (message.tokens) {
                        parts.push((this.labels.tokens || '%s').replace('%s', message.tokens.toLocaleString()));
                    }
                    return parts.join(' · ');
                },
            };
        },
        messageText(message) {
            return {
                'u-show'() {
                    return Boolean(message.text);
                },
                'u-text'() {
                    return message.text;
                },
            };
        },
        listOf(message, field) {
            return {
                'u-show'() {
                    return Array.isArray(message[field]) && message[field].length > 0;
                },
            };
        },
        lineText(line) {
            return {
                'u-text'() {
                    return line;
                },
            };
        },
        specification(message) {
            return {
                'u-show'() {
                    return Boolean(message.specification);
                },
            };
        },
        specificationText(message) {
            return {
                'u-text'() {
                    return message.specification;
                },
            };
        },
        fileTitle(file) {
            return {
                'u-text'() {
                    return `${file.path} · ${file.size}`;
                },
            };
        },
        fileContent(file) {
            return {
                'u-text'() {
                    return file.content;
                },
            };
        },
        messageTime(message) {
            return {
                'u-show'() {
                    return message.role !== 'step';
                },
                'u-text'() {
                    return this.formatTime(message.time);
                },
            };
        },
        progressBar: {
            'u-show'() {
                return this.isRunning || this.sending;
            },
        },
        // the worker has not reported a step yet: queued or just started
        thinkingIndicator: {
            'u-show'() {
                const last = this.messages[this.messages.length - 1];
                return this.sending || (this.isRunning && last?.role !== 'step');
            },
        },
        thinkingText: {
            'u-text'() {
                return this.labels.queued;
            },
        },
        notice: {
            'u-show'() {
                return Boolean(this.error) || !this.configured;
            },
            'u-text'() {
                return this.error || this.labels.notConfigured;
            },
        },
        composerInput: {
            'u-prop': 'draft',
            ':placeholder'() {
                return this.isAsking ? this.labels.answer : this.labels.ask;
            },
            ':disabled'() {
                return this.isRunning || this.sending;
            },
            '@keydown.enter.prevent'() {
                this.send();
            },
        },
        sendButton: {
            'u-show'() {
                return !this.isRunning;
            },
            ':disabled'() {
                return !this.draft.trim() || this.sending;
            },
            '@click'() {
                this.send();
            },
        },
        stopButton: {
            'u-show'() {
                return this.isRunning;
            },
            '@click'() {
                this.stop();
            },
        },
    }));
});
