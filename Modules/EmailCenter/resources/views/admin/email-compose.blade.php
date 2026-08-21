@section('title', 'Compose & Send | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-wrap-narrow">
    <a href="{{ route('admin.email.logs') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Send History
    </a>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Messaging</span>
            <h1 class="pc-title">Compose &amp; Send</h1>
        </div>
    </div>

    <div class="pc-card">
        <form id="email-compose-form" action="{{ route('admin.email.send') }}" method="POST">
            @csrf

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="template-select">Start from a template (optional)</label>
                    <select id="template-select" class="pc-field__input">
                        <option value="">— Blank message —</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}" data-subject="{{ $t->subject }}" data-body="{{ $t->body_markdown }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <input type="hidden" name="template_id" id="template_id" value="">

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="subject">Subject</label>
                    <input id="subject" type="text" name="subject" class="pc-field__input" value="{{ old('subject') }}" maxlength="150" required placeholder="Supports {name} and {email} placeholders">
                    @error('subject')<span class="pc-field__error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="body">Body (Markdown)</label>
                    <textarea id="body" name="body" rows="14" required placeholder="# Hello {name}

Write your announcement in Markdown...">{{ old('body') }}</textarea>
                    <p class="pc-field__hint">Placeholders: <code>{name}</code>, <code>{email}</code> — replaced per recipient. Max 10,000 characters.</p>
                    @error('body')<span class="pc-field__error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <span class="pc-field__label">Recipients</span>
                    @foreach($groups as $key => $label)
                        <label class="inline-label">
                            <input type="radio" name="group" value="{{ $key }}" {{ old('group') === $key ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                    @error('group')<span class="pc-field__error">{{ $message }}</span>@enderror
                    <label class="inline-label">
                        <input type="checkbox" name="newsletter_only" value="1">
                        Only newsletter subscribers
                    </label>
                </div>
            </div>

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="user-search">Or pick individuals</label>
                    <input id="user-search" type="text" class="pc-field__input" autocomplete="off" placeholder="Search by name or email…">
                    <div id="user-search-results"></div>
                    <div id="selected-users"></div>
                    <p class="pc-field__hint">Individual picks override the group above. Limit 100 recipients.</p>
                </div>
            </div>

            <div class="pc-form-actions">
                <button type="submit" class="btn btn-primary pc-btn-sm">Send</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var select = document.getElementById('template-select');
    var templateId = document.getElementById('template_id');
    var subject = document.getElementById('subject');
    var body = document.getElementById('body');
    var search = document.getElementById('user-search');
    var results = document.getElementById('user-search-results');
    var chosen = document.getElementById('selected-users');
    var picked = {};

    select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        if (!opt.value) {
            templateId.value = '';
            return;
        }
        if (subject.value.trim() === '' && body.value.trim() === '') {
            subject.value = opt.getAttribute('data-subject') || '';
            body.value = opt.getAttribute('data-body') || '';
        }
        templateId.value = opt.value;
    });

    function renderChosen() {
        chosen.textContent = '';
        Object.keys(picked).forEach(function (id) {
            var chip = document.createElement('span');
            chip.className = 'chip';
            chip.textContent = picked[id] + ' ';
            var x = document.createElement('button');
            x.type = 'button';
            x.textContent = 'x';
            x.addEventListener('click', function () {
                delete picked[id];
                renderChosen();
            });
            chip.appendChild(x);
            chosen.appendChild(chip);
        });
    }

    function renderResults(users) {
        results.textContent = '';
        users.forEach(function (u) {
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'user-result';
            row.textContent = u.name + ' (' + u.email + ')';
            row.addEventListener('click', function () {
                picked[u.id] = u.name;
                renderChosen();
                results.textContent = '';
                search.value = '';
            });
            results.appendChild(row);
        });
    }

    var timer = null;
    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('{{ $userSearchUrl }}?q=' + encodeURIComponent(search.value))
                .then(function (r) { return r.json(); })
                .then(renderResults);
        }, 250);
    });

    document.getElementById('email-compose-form').addEventListener('submit', function () {
        Object.keys(picked).forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = id;
            this.appendChild(input);
        }, this);
    });
})();
</script>

</x-app-layout>