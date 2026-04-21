@extends('layouts.app')

@php
    $statusMeta = [
        'resolved' => ['label' => 'Resolved', 'tone' => 'is-resolved'],
        'pending' => ['label' => 'Pending', 'tone' => 'is-pending'],
        'needs_manager_attention' => ['label' => 'Needs Manager Attention', 'tone' => 'is-attention'],
    ];

    $themeLabels = [
        'blue' => 'Blue + White',
        'green' => 'Green + White',
        'gray' => 'Gray + White',
    ];

    $chatRoleMeta = [
        'manager' => [
            'compose_label' => 'Send to Ronald from Manager',
            'author_label' => 'Manager',
            'button_class' => 'is-manager',
        ],
        'associate' => [
            'compose_label' => 'Send to Manager from Ronald',
            'author_label' => 'Ronald',
            'button_class' => 'is-ronald',
        ],
    ];

    $rafflePoolIds = array_merge([
        'sun_wed' => [],
        'wed_sat' => [],
        'part_time' => [],
        'unavailable' => [],
    ], $rafflePoolIds ?? []);

    $activeSection = $context['section'] ?? 'section-notes';
@endphp

@section('content')
<div class="dashboard-app">
    <div class="dashboard-glow glow-a"></div>
    <div class="dashboard-glow glow-b"></div>

    <aside class="side-nav" id="side-nav">
        <div class="side-brand">
            <p class="side-kicker">ICQA Workspace</p>
            <h1>Personalize Dashboard</h1>
            <p>Shared account for manager and associate collaboration.</p>
        </div>

        <button type="button" class="side-close" data-sidebar-close aria-label="Close sidebar">&times;</button>

        <nav class="side-menu">
            <button type="button" class="side-link {{ $activeSection === 'section-notes' ? 'is-active' : '' }}" data-section-btn data-target="section-notes">Hourly Notes</button>
            <button type="button" class="side-link {{ $activeSection === 'section-chat' ? 'is-active' : '' }}" data-section-btn data-target="section-chat">Chat Thread</button>
            <button type="button" class="side-link {{ $activeSection === 'section-schedule' ? 'is-active' : '' }}" data-section-btn data-target="section-schedule">Scheduling</button>
            <button type="button" class="side-link {{ $activeSection === 'section-process' ? 'is-active' : '' }}" data-section-btn data-target="section-process">Process Path</button>
        </nav>

        <div class="side-bottom">
            <small class="sidebar-account-title">Shared account</small>
            <div class="profile-chip sidebar-profile-chip">
                <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->email }}</small>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-block" data-open-modal="logout-modal">Logout</button>
        </div>
    </aside>

    <div class="app-content">
        <header class="top-header">
            <div class="header-left">
                <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle aria-label="Toggle sidebar">
                    <span class="icon-open">&#9776;</span>
                    <span class="icon-close">&times;</span>
                </button>
                <div>
                    <p class="top-kicker">Hourly Associate Feedback and Concern</p>
                    <h2>ICQA Dashboard</h2>
                </div>
            </div>

            <div class="header-right">
                <button type="button" class="btn btn-secondary header-settings-btn" data-open-modal="profile-modal">
                    <span class="settings-circle">⚙</span>
                    <span>Profile Settings</span>
                </button>
            </div>
        </header>

        @if (session('success'))
            <div class="toast-stack">
                <div class="toast toast-success" data-toast>
                    <p>{{ session('success') }}</p>
                    <div class="toast-progress"></div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <main class="section-stack">
            <section id="section-notes" class="content-section {{ $activeSection === 'section-notes' ? 'is-active' : '' }}" data-dashboard-section>
                <article class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>Hourly Notes</h3>
                            <p>Track concerns per hour with clear status highlights.</p>
                        </div>
                        <form method="GET" action="{{ route('dashboard') }}" class="inline-form">
                            <input type="date" name="date" value="{{ $selectedDate }}" required>
                            <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                            <input type="hidden" name="section" value="section-notes">
                            <button type="submit" class="btn btn-primary btn-sm">View</button>
                        </form>
                    </div>

                    <div class="status-overview">
                        <span class="status-chip is-resolved">Resolved: {{ $statusOverview['resolved'] }}</span>
                        <span class="status-chip is-pending">Pending: {{ $statusOverview['pending'] }}</span>
                        <span class="status-chip is-attention">Needs Manager Attention: {{ $statusOverview['needs_manager_attention'] }}</span>
                        <span class="status-chip is-total">Total: {{ $statusOverview['total'] }}</span>
                    </div>

                    <div class="note-list">
                        @foreach ($hourlyNotes as $note)
                            @php
                                $displayHour = \Carbon\Carbon::createFromFormat('H:i', $note->hour_slot)->format('h:i A');
                                $status = $note->status ?: 'pending';
                                $noteText = $note->note ?? '';
                                $managerComment = $note->manager_comment ?? '';
                            @endphp
                            <form method="POST" action="{{ route('notes.upsert') }}" class="note-card {{ $statusMeta[$status]['tone'] }}" data-note-card>
                                @csrf
                                <input type="hidden" name="note_date" value="{{ $selectedDate }}">
                                <input type="hidden" name="hour_slot" value="{{ $note->hour_slot }}">
                                <input type="hidden" name="date" value="{{ $selectedDate }}">
                                <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                                <input type="hidden" name="section" value="section-notes">

                                <div class="note-head">
                                    <h4>{{ $displayHour }}</h4>
                                    <button type="button" class="btn-link note-expand" data-toggle-note>Expand note</button>
                                </div>

                                <textarea
                                    name="note"
                                    class="note-textarea"
                                    rows="3"
                                    placeholder="Write notes for this hour..."
                                    data-note-input
                                >{{ $noteText }}</textarea>

                                <div class="manager-note-block">
                                    <p class="manager-note-label">Manager Comment</p>
                                    <textarea
                                        name="manager_comment"
                                        class="note-textarea manager-note-textarea"
                                        rows="2"
                                        placeholder="Manager can leave comments for this note..."
                                    >{{ $managerComment }}</textarea>
                                </div>

                                <div class="note-status-row">
                                    @foreach ($statusMeta as $statusKey => $meta)
                                        @php $id = 'status-' . str_replace(':', '', $note->hour_slot) . '-' . $statusKey; @endphp
                                        <label for="{{ $id }}" class="status-pill {{ $meta['tone'] }}">
                                            <input id="{{ $id }}" type="radio" name="status" value="{{ $statusKey }}" {{ $status === $statusKey ? 'checked' : '' }}>
                                            <span>{{ $meta['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="note-footer">
                                    <small><span data-note-words>0</span> / 600 words</small>
                                    <button type="submit" class="btn btn-primary btn-sm">Save Note</button>
                                </div>
                            </form>
                        @endforeach
                    </div>
                </article>
            </section>

            <section id="section-chat" class="content-section {{ $activeSection === 'section-chat' ? 'is-active' : '' }}" data-dashboard-section>
                <article class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>Chat Thread</h3>
                            <p>Single two-way conversation for manager and associate.</p>
                        </div>
                    </div>

                    <div class="chat-thread" id="chat-thread">
                        @forelse ($chatMessages as $message)
                            <article class="chat-row {{ $message->sender_role === 'associate' ? 'is-associate' : 'is-manager' }}">
                                <div class="chat-bubble">
                                    <p class="chat-author">{{ $chatRoleMeta[$message->sender_role]['author_label'] ?? ucfirst($message->sender_role) }}</p>
                                    <p class="chat-message">{!! nl2br(e($message->message)) !!}</p>
                                    <small>{{ $message->created_at->format('g:i A') }}</small>
                                </div>
                            </article>
                        @empty
                            <div class="chat-empty">No messages yet. Start the first update.</div>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('chat.store') }}" class="chat-form">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                        <input type="hidden" name="section" value="section-chat">

                        @php $selectedSenderRole = old('sender_role', 'manager'); @endphp

                        <div class="role-picker">
                            <label class="role-pill {{ $chatRoleMeta['manager']['button_class'] }}">
                                <input type="radio" name="sender_role" value="manager" {{ $selectedSenderRole === 'manager' ? 'checked' : '' }}>
                                <span>{{ $chatRoleMeta['manager']['compose_label'] }}</span>
                            </label>
                            <label class="role-pill {{ $chatRoleMeta['associate']['button_class'] }}">
                                <input type="radio" name="sender_role" value="associate" {{ $selectedSenderRole === 'associate' ? 'checked' : '' }}>
                                <span>{{ $chatRoleMeta['associate']['compose_label'] }}</span>
                            </label>
                        </div>

                        <div class="emoji-row">
                            @foreach (['OK', 'FYI', 'URGENT', 'DONE', 'CHECK', 'UPDATE'] as $quickTag)
                                <button type="button" class="emoji-chip" data-emoji="{{ $quickTag }}">{{ $quickTag }}</button>
                            @endforeach
                        </div>

                        <textarea id="chat-input" name="message" rows="4" maxlength="600" placeholder="Type your message..." required></textarea>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </article>
            </section>

            <section id="section-schedule" class="content-section {{ $activeSection === 'section-schedule' ? 'is-active' : '' }}" data-dashboard-section>
                <article class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>Scheduling</h3>
                            <p>Auto-assigns daily Main and Support names, with quick override when someone is absent.</p>
                        </div>
                    </div>

                    <div class="schedule-controls">
                        <form method="POST" action="{{ route('schedule.generate') }}" class="inline-form">
                            @csrf
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                            <input type="hidden" name="section" value="section-schedule">
                            <input type="month" name="month" value="{{ $selectedMonth->format('Y-m') }}" required>
                            <button type="submit" class="btn btn-primary btn-sm">Auto Assign Month</button>
                        </form>

                        <form method="POST" action="{{ route('schedule.theme') }}" class="theme-picker">
                            @csrf
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                            <input type="hidden" name="section" value="section-schedule">

                            @foreach ($themeLabels as $themeKey => $themeLabel)
                                <label>
                                    <input type="radio" name="schedule_theme" value="{{ $themeKey }}" {{ $scheduleTheme === $themeKey ? 'checked' : '' }} onchange="this.form.submit()">
                                    <span>{{ $themeLabel }}</span>
                                </label>
                            @endforeach
                        </form>
                    </div>

                    <form method="POST" action="{{ route('associates.store') }}" class="add-associate-form">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                        <input type="hidden" name="section" value="section-schedule">
                        <input type="text" name="name" placeholder="Add associate name..." maxlength="80" required>
                        <button type="submit" class="btn btn-secondary btn-sm">Add Associate</button>
                    </form>

                    <div class="associate-pills">
                        @foreach ($associates as $associate)
                            <span class="associate-pill">
                                {{ $associate->name }}
                                <form method="POST" action="{{ route('associates.destroy', $associate) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                                    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                    <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                                    <input type="hidden" name="section" value="section-schedule">
                                    <button type="submit" title="Hide associate">x</button>
                                </form>
                            </span>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('schedule.pools') }}" class="raffle-pool-form">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                        <input type="hidden" name="section" value="section-schedule">

                        <div class="raffle-pool-head">
                            <h4>Raffle Groups</h4>
                            <p>Set Main pools per day type, keep Part-time associates visible, and assign Support/Backup pool.</p>
                        </div>

                        <div class="raffle-pool-table-wrap">
                            <table class="raffle-pool-table">
                                <thead>
                                    <tr>
                                        <th>Associate</th>
                                        <th>Main Pool (Sun-Wed)</th>
                                        <th>Main Pool (Thu-Sat)</th>
                                        <th>Part-time Associates</th>
                                        <th>Support / Backup Pool</th>
                                        <th>Skip Raffle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($associates as $associate)
                                        <tr>
                                            <td>{{ $associate->name }}</td>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="sun_wed_ids[]"
                                                    value="{{ $associate->id }}"
                                                    @checked(in_array($associate->id, $rafflePoolIds['sun_wed'], true))
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="wed_sat_ids[]"
                                                    value="{{ $associate->id }}"
                                                    @checked(in_array($associate->id, $rafflePoolIds['wed_sat'], true))
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="part_time_ids[]"
                                                    value="{{ $associate->id }}"
                                                    @checked(in_array($associate->id, $rafflePoolIds['part_time'], true))
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="support_ids[]"
                                                    value="{{ $associate->id }}"
                                                    @checked(in_array($associate->id, $rafflePoolIds['support'], true))
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="unavailable_ids[]"
                                                    value="{{ $associate->id }}"
                                                    @checked(in_array($associate->id, $rafflePoolIds['unavailable'], true))
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="raffle-pool-actions">
                            <button type="submit" class="btn btn-secondary btn-sm">Save Raffle Groups</button>
                            <small>Checked in Skip Raffle means vacation/unavailable for auto-assignment.</small>
                        </div>
                    </form>

                    <div class="schedule-scroll">
                        <div class="schedule-board schedule-theme-{{ $scheduleTheme }}">
                            <p class="schedule-auto-note">Main and Support selections save automatically.</p>
                            <div class="calendar-header">
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayName)
                                    <div>{{ $dayName }}</div>
                                @endforeach
                            </div>
                            @foreach ($calendarWeeks as $week)
                                <div class="calendar-week">
                                    @foreach ($week as $day)
                                        @php $schedule = $day['schedule']; @endphp
                                        <article class="calendar-day {{ $day['is_current_month'] ? '' : 'is-muted' }}">
                                            <header>{{ $day['day_number'] }}</header>

                                            <form method="POST" action="{{ route('schedule.update') }}" class="day-form">
                                                @csrf
                                                <input type="hidden" name="schedule_date" value="{{ $day['iso_date'] }}">
                                                <input type="hidden" name="date" value="{{ $selectedDate }}">
                                                <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                                <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                                                <input type="hidden" name="section" value="section-schedule">

                                                <label>
                                                    <span>Main</span>
                                                    <select name="shift_a_associate_id" class="day-assignment-select" onchange="this.form.submit()">
                                                        <option value="">-</option>
                                                        @foreach ($associates as $associate)
                                                            <option value="{{ $associate->id }}" @selected(optional($schedule)->shift_a_associate_id === $associate->id)>
                                                                {{ $associate->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </label>

                                                <label>
                                                    <span>Support</span>
                                                    <select name="shift_b_associate_id" class="day-assignment-select" onchange="this.form.submit()">
                                                        <option value="">-</option>
                                                        @foreach ($associates as $associate)
                                                            <option value="{{ $associate->id }}" @selected(optional($schedule)->shift_b_associate_id === $associate->id)>
                                                                {{ $associate->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                            </form>
                                        </article>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            </section>

            <section id="section-process" class="content-section {{ $activeSection === 'section-process' ? 'is-active' : '' }}" data-dashboard-section>
                <article class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>Process Path</h3>
                            <p>Editable associate names with up to three process paths (P1, P2, P3).</p>
                        </div>
                        <form method="GET" action="{{ route('dashboard') }}" class="inline-form">
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                            <input type="hidden" name="section" value="section-process">
                            <select name="year">
                                @for ($year = now()->year - 1; $year <= now()->year + 2; $year++)
                                    <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                                @endfor
                            </select>
                            <select name="quarter">
                                @for ($quarter = 1; $quarter <= 4; $quarter++)
                                    <option value="{{ $quarter }}" @selected($selectedQuarter === $quarter)>Q{{ $quarter }}</option>
                                @endfor
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">View</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('process-path.update') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                        <input type="hidden" name="section" value="section-process">

                        <div class="process-table-wrap">
                            <table class="process-table">
                                <thead>
                                    <tr>
                                        <th>Associate Name</th>
                                        <th>P1</th>
                                        <th>P2</th>
                                        <th>P3</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($associates as $index => $associate)
                                        @php
                                            $assignment = $processAssignments->get($associate->id);
                                            $path1 = $assignment?->path_1 ?? $assignment?->start_path ?? '';
                                            $path2 = $assignment?->path_2 ?? $assignment?->end_path ?? '';
                                            $path3 = $assignment?->path_3 ?? '';
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="hidden" name="assignments[{{ $index }}][associate_id]" value="{{ $associate->id }}">
                                                <input type="text" name="assignments[{{ $index }}][associate_name]" value="{{ old("assignments.$index.associate_name", $associate->name) }}" maxlength="80" required>
                                            </td>
                                            <td>
                                                <input type="text" name="assignments[{{ $index }}][path_1]" value="{{ old("assignments.$index.path_1", $path1) }}" maxlength="80" placeholder="P1">
                                            </td>
                                            <td>
                                                <input type="text" name="assignments[{{ $index }}][path_2]" value="{{ old("assignments.$index.path_2", $path2) }}" maxlength="80" placeholder="P2">
                                            </td>
                                            <td>
                                                <input type="text" name="assignments[{{ $index }}][path_3]" value="{{ old("assignments.$index.path_3", $path3) }}" maxlength="80" placeholder="P3">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="process-actions">
                            <button type="submit" class="btn btn-primary">Save Process Paths</button>
                            <a
                                href="{{ route('process-path.print', ['year' => $selectedYear, 'quarter' => $selectedQuarter, 'date' => $selectedDate, 'month' => $selectedMonth->format('Y-m')]) }}"
                                class="btn btn-secondary"
                                target="_blank"
                            >
                                Printable View
                            </a>
                        </div>
                    </form>
                </article>
            </section>
        </main>
    </div>
</div>

<div class="modal-backdrop" data-modal="logout-modal">
    <div class="modal-card">
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to logout from this shared dashboard account?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger">Yes, Logout</button>
            </form>
        </div>
    </div>
</div>

<div class="modal-backdrop" data-modal="profile-modal">
    <div class="modal-card modal-lg">
        <h3>Profile Settings</h3>
        <p>Update the shared account details used by associate and manager.</p>

        <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
            @csrf
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
            <input type="hidden" name="section" id="profile-section-input" value="{{ $activeSection }}">

            <label>
                <span>Name</span>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" maxlength="80" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" maxlength="120" required>
            </label>
            <label>
                <span>New Password (optional)</span>
                <input type="password" name="password" minlength="6">
            </label>
            <label>
                <span>Confirm New Password</span>
                <input type="password" name="password_confirmation" minlength="6">
            </label>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>

        <div class="settings-divider"></div>

        <section class="settings-block">
            <h4>Chat Settings</h4>
            <p>Need to clear the whole thread? You can reset the chat conversation here.</p>

            <button type="button" class="btn btn-danger reset-chat-form" data-open-modal="reset-chat-modal">
                Reset Chat Conversation
            </button>
        </section>
    </div>
</div>

<div class="modal-backdrop" data-modal="reset-chat-modal">
    <div class="modal-card">
        <h3>Reset Chat Conversation</h3>
        <p>This will permanently remove all messages from the chat thread. Continue?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
            <form method="POST" action="{{ route('chat.reset') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                <input type="hidden" name="section" value="section-chat">
                <button type="submit" class="btn btn-danger">Yes, Reset Chat</button>
            </form>
        </div>
    </div>
</div>
@endsection
