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

    $pathOptions = ['SBC', 'SRC', 'CC'];
@endphp

@section('content')
<div class="app-shell">
    <div class="app-ambient ambient-left"></div>
    <div class="app-ambient ambient-right"></div>

    <header class="dashboard-header">
        <div>
            <p class="kicker">Hourly Associate Feedback and Concern</p>
            <h1>ICQA Dashboard</h1>
            <p class="header-caption">Shared login for associate + manager with live notes, chat, scheduling, and process path records.</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary">Logout</button>
        </form>
    </header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <main class="dashboard-grid">
        <section class="panel panel-notes">
            <div class="panel-head">
                <div>
                    <h2>Hourly Notes</h2>
                    <p>Track concerns per hour with clear status highlight for manager follow-up.</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="inline-form">
                    <input type="date" name="date" value="{{ $selectedDate }}" required>
                    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
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
                    @endphp
                    <form method="POST" action="{{ route('notes.upsert') }}" class="note-card {{ $statusMeta[$status]['tone'] }}" data-note-card>
                        @csrf
                        <input type="hidden" name="note_date" value="{{ $selectedDate }}">
                        <input type="hidden" name="hour_slot" value="{{ $note->hour_slot }}">
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">

                        <div class="note-head">
                            <h3>{{ $displayHour }}</h3>
                            <button type="button" class="btn-link note-expand" data-toggle-note>Expand note</button>
                        </div>

                        <textarea
                            name="note"
                            class="note-textarea"
                            rows="3"
                            placeholder="Write notes for this hour..."
                            data-note-input
                        >{{ $noteText }}</textarea>

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
        </section>

        <aside class="panel panel-chat">
            <div class="panel-head">
                <div>
                    <h2>Chat</h2>
                    <p>Single 2-way thread for manager and associate updates.</p>
                </div>
            </div>

            <div class="chat-thread" id="chat-thread">
                @forelse ($chatMessages as $message)
                    <article class="chat-row {{ $message->sender_role === 'associate' ? 'is-associate' : 'is-manager' }}">
                        <div class="chat-bubble">
                            <p class="chat-author">{{ ucfirst($message->sender_role) }}</p>
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

                <div class="role-picker">
                    <label>
                        <input type="radio" name="sender_role" value="associate" checked>
                        <span>Send as Associate</span>
                    </label>
                    <label>
                        <input type="radio" name="sender_role" value="manager">
                        <span>Send as Manager</span>
                    </label>
                </div>

                <div class="emoji-row">
                    @foreach (['👍', '✅', '🔥', '👏', '💯', '📌', '🙂', '⚠️'] as $emoji)
                        <button type="button" class="emoji-chip" data-emoji="{{ $emoji }}">{{ $emoji }}</button>
                    @endforeach
                </div>

                <textarea id="chat-input" name="message" rows="3" maxlength="600" placeholder="Type your message..." required></textarea>
                <button type="submit" class="btn btn-primary btn-block">Send Message</button>
            </form>
        </aside>

        <section class="panel panel-schedule">
            <div class="panel-head">
                <div>
                    <h2>Scheduling</h2>
                    <p>Auto-assign rotation for up to 20 associates with manual per-day adjustments.</p>
                </div>
            </div>

            <div class="schedule-controls">
                <form method="POST" action="{{ route('schedule.generate') }}" class="inline-form">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                    <input type="month" name="month" value="{{ $selectedMonth->format('Y-m') }}" required>
                    <button type="submit" class="btn btn-primary btn-sm">Auto Assign Month</button>
                </form>

                <form method="POST" action="{{ route('schedule.theme') }}" class="theme-picker">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">

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
                            <button type="submit" title="Hide associate">×</button>
                        </form>
                    </span>
                @endforeach
            </div>

            <div class="schedule-board schedule-theme-{{ $scheduleTheme }}">
                <div class="calendar-header">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
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

                                    <label>
                                        <span>A</span>
                                        <select name="shift_a_associate_id">
                                            <option value="">-</option>
                                            @foreach ($associates as $associate)
                                                <option value="{{ $associate->id }}" @selected(optional($schedule)->shift_a_associate_id === $associate->id)>
                                                    {{ $associate->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label>
                                        <span>B</span>
                                        <select name="shift_b_associate_id">
                                            <option value="">-</option>
                                            @foreach ($associates as $associate)
                                                <option value="{{ $associate->id }}" @selected(optional($schedule)->shift_b_associate_id === $associate->id)>
                                                    {{ $associate->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <button type="submit" class="btn btn-ghost btn-xs">Save</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel panel-process">
            <div class="panel-head">
                <div>
                    <h2>Process Path</h2>
                    <p>Quarterly path record per associate: SBC, SRC, or CC with printable history.</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="inline-form">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
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

                <div class="process-table-wrap">
                    <table class="process-table">
                        <thead>
                            <tr>
                                <th>Associate</th>
                                <th>1st Path</th>
                                <th>2nd Path</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($associates as $index => $associate)
                                @php
                                    $assignment = $processAssignments->get($associate->id);
                                    $startPath = $assignment?->start_path ?? 'SBC';
                                    $endPath = $assignment?->end_path ?? 'SRC';
                                @endphp
                                <tr>
                                    <td>{{ $associate->name }}</td>
                                    <td>
                                        <input type="hidden" name="assignments[{{ $index }}][associate_id]" value="{{ $associate->id }}">
                                        <select name="assignments[{{ $index }}][start_path]">
                                            @foreach ($pathOptions as $pathOption)
                                                <option value="{{ $pathOption }}" @selected($startPath === $pathOption)>{{ $pathOption }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="assignments[{{ $index }}][end_path]">
                                            @foreach ($pathOptions as $pathOption)
                                                <option value="{{ $pathOption }}" @selected($endPath === $pathOption)>{{ $pathOption }}</option>
                                            @endforeach
                                        </select>
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
        </section>
    </main>
</div>
@endsection
