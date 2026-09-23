<?php

namespace App\Http\Controllers\Chat;

use App\Events\Chat\MessageSent;
use App\Events\Chat\MessageDeleted;
use App\Events\Chat\MessagesRead;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Services\Chat\ChatContacts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function avatar(\App\Models\User $user)
    {
        $mediaPath = $user->getRawOriginal('media');
        // Older records contain either `/storage/...` URLs or stale files. Use
        // the real public-disk path when available; otherwise return a valid
        // generated initials avatar instead of a broken image response.
        $path = preg_replace('#^/?storage/#', '', (string) $mediaPath);
        if ($path && !str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path, null, ['Content-Disposition' => 'inline']);
        }

        $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') ?: '?';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160">'
            .'<rect width="160" height="160" rx="80" fill="#dbece4"/>'
            .'<text x="80" y="93" text-anchor="middle" font-family="Arial, sans-serif" font-size="52" font-weight="700" fill="#24674d">'
            .e($initials).'</text></svg>';
        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function attachment(MessageAttachment $attachment)
    {
        abort_unless(Storage::disk('public')->exists($attachment->path), 404);
        return Storage::disk('public')->response($attachment->path, $attachment->name, [
            'Content-Type' => $attachment->mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->name).'"',
        ]);
    }

    public function legacyAttachment(Message $message)
    {
        abort_unless($message->attachment_path && Storage::disk('public')->exists($message->attachment_path), 404);
        return Storage::disk('public')->response($message->attachment_path, $message->attachment_name, [
            'Content-Type' => $message->attachment_mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($message->attachment_name).'"',
        ]);
    }

    private function active(Request $request): void
    {
        abort_unless((int) $request->user()->status === 1 && $request->user()->deleted_at === null, 403);
    }

    public function contacts(Request $request, ChatContacts $contacts)
    {
        $this->active($request);
        $data = $request->validate(['search' => 'nullable|string|max:100', 'page' => 'sometimes|integer|min:1']);
        $query = $contacts->query($request->user())->with('roles');
        if (!empty($data['search'])) {
            $search = mb_strtolower($data['search']);
            $compactSearch = str_replace([' ', '-'], '', $search);
            $query->where(function ($matches) use ($search, $compactSearch) {
                $matches->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                    // "superadmin" should find an account named "Super Admin".
                    ->orWhereRaw("REPLACE(REPLACE(LOWER(name), ' ', ''), '-', '') LIKE ?", ['%'.addcslashes($compactSearch, '%_\\').'%']);
            });
        }
        return $query->orderBy('name')->orderBy('id')->simplePaginate(30)
            ->through(fn ($user) => ChatContacts::summary($user));
    }

    public function session(Request $request)
    {
        $this->active($request);
        return response()->json(['user' => ChatContacts::summary($request->user())]);
    }

    public function unread(Request $request)
    {
        $this->active($request);
        $userId = $request->user()->id;
        $count = Message::whereNull('read_at')->where('sender_id', '!=', $userId)
            ->whereHas('conversation.participants', fn ($q) => $q->where('user_id', $userId))->count();
        return response()->json(['user_id' => $userId, 'unread_count' => $count]);
    }

    public function index(Request $request, ChatContacts $contacts)
    {
        $this->active($request);
        $request->validate(['page' => 'sometimes|integer|min:1']);
        $user = $request->user();
        $contactIds = $contacts->query($user)->select('users.id');
        $conversations = $user->conversations()
            ->whereHas('participants', fn ($participants) => $participants->where('user_id', $user->id)->whereNull('hidden_at'))
            ->whereHas('participants', fn ($participants) => $participants
                ->where('user_id', '!=', $user->id)->whereIn('user_id', $contactIds))
            ->with(['participants.user' => fn ($q) => $q->without(['student', 'monitor', 'secretary'])->with('roles'), 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', $user->id)->whereNull('read_at')])
            ->orderByDesc('conversations.updated_at')->orderByDesc('conversations.id')->simplePaginate(30);

        return $conversations->through(fn ($conversation) => $this->summary($conversation));
    }

    private function summary(Conversation $conversation): array
    {
        $participant = $conversation->participants->firstWhere('user_id', request()->user()->id);
        $lastMessage = $conversation->latestMessage;
        if ($participant?->cleared_at && $lastMessage?->created_at?->lte($participant->cleared_at)) $lastMessage = null;
        return [
            'id' => $conversation->id, 'type' => $conversation->type,
            'updated_at' => $conversation->updated_at, 'unread_count' => $conversation->unread_count ?? 0,
            'last_message' => $lastMessage,
            'participants' => $conversation->participants->map(fn ($participant) => [
                'user_id' => $participant->user_id, 'last_read_message_id' => $participant->last_read_message_id,
                'user' => $participant->user ? ChatContacts::summary($participant->user) : [
                    'id' => $participant->user_id, 'name' => 'Compte indisponible', 'role' => '',
                ],
            ]),
        ];
    }

    public function store(Request $request, ChatContacts $contacts)
    {
        $this->active($request);
        $data = $request->validate(['user_id' => 'required|integer|min:1']);
        abort_unless($contacts->query($request->user())->whereKey($data['user_id'])->exists(), 403);
        $ids = [(int) $request->user()->id, (int) $data['user_id']];
        sort($ids, SORT_NUMERIC);
        $conversation = DB::transaction(function () use ($ids) {
            $conversation = Conversation::firstOrCreate(['private_key' => hash('sha256', implode(':', $ids))], ['type' => 'private']);
            foreach ($ids as $id) {
                $participant = $conversation->participants()->firstOrCreate(['user_id' => $id], ['joined_at' => now()]);
                // A user who previously removed this thread from their own list
                // gets it back only when they deliberately select that contact again.
                if ($id === (int) request()->user()->id && $participant->hidden_at) {
                    $participant->update(['hidden_at' => null]);
                }
            }
            return $conversation;
        });
        $conversation->load(['participants.user' => fn ($q) => $q->without(['student', 'monitor', 'secretary'])->with('roles'), 'latestMessage']);
        $conversation->loadCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')]);
        return response()->json(['data' => $this->summary($conversation)], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        Gate::authorize('view', $conversation);
        $data = $request->validate([
            'before_id' => 'sometimes|integer|min:1|prohibits:after_id',
            'after_id' => 'sometimes|integer|min:0|prohibits:before_id',
        ]);
        $query = $conversation->messages()->with('attachments');
        $clearedAt = $conversation->participants()->where('user_id', $request->user()->id)->value('cleared_at');
        if ($clearedAt) $query->where('messages.created_at', '>', $clearedAt);
        if (isset($data['before_id'])) {
            $query->where('id', '<', $data['before_id']);
        }
        $forward = isset($data['after_id']);
        if ($forward) {
            $query->where('id', '>', $data['after_id']);
        }
        $messages = $query->orderBy('id', $forward ? 'asc' : 'desc')->limit(51)->get();
        $hasMore = $messages->count() > 50;
        $messages = $messages->take(50)->sortBy('id')->values();
        return response()->json([
            'data' => $messages, 'has_more' => $hasMore,
            'participants' => $conversation->participants()->get(['user_id', 'last_read_message_id']),
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        Gate::authorize('send', $conversation);
        if (is_string($request->input('message'))) {
            $request->merge(['message' => preg_replace('/^\s+|\s+$/u', '', $request->input('message'))]);
        }
        $data = $request->validate([
            // A message can contain text, the legacy single `attachment`, or
            // the current multi-file `attachments[]` payload.
            'message' => 'nullable|string|max:4000|required_without_all:attachment,attachments', 'message_type' => 'sometimes|in:text',
            'client_message_id' => 'nullable|uuid',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
            'attachments' => 'nullable|array|max:8',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
        ]);
        $attachments = $request->file('attachments', []);
        if ($legacyAttachment = $request->file('attachment')) $attachments[] = $legacyAttachment;
        if (blank($data['message'] ?? null) && !$attachments) {
            throw ValidationException::withMessages(['message' => 'Ajoutez un message ou une pièce jointe.']);
        }
        $message = DB::transaction(function () use ($conversation, $request, $data, $attachments) {
            // Serialize sends/reads per thread, including concurrent browser tabs.
            Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            if (!empty($data['client_message_id'])) {
                $existing = Message::where('sender_id', $request->user()->id)->where('client_message_id', $data['client_message_id'])->first();
                if ($existing) {
                    abort_unless($existing->conversation_id === $conversation->id && $existing->message === ($data['message'] ?? ''), 409);
                    return $existing;
                }
            }
            $firstAttachment = $attachments[0] ?? null;
            $path = $firstAttachment?->store('chat-attachments', 'public');
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id, 'message' => $data['message'] ?? '', 'message_type' => $attachments ? 'attachment' : 'text',
                'client_message_id' => $data['client_message_id'] ?? null,
                'attachment_path' => $path, 'attachment_name' => $firstAttachment?->getClientOriginalName(),
                'attachment_mime' => $firstAttachment?->getMimeType(), 'attachment_size' => $firstAttachment?->getSize(),
            ]);
            if ($firstAttachment) $message->attachments()->create(['path' => $path, 'name' => $firstAttachment->getClientOriginalName(), 'mime' => $firstAttachment->getMimeType(), 'size' => $firstAttachment->getSize()]);
            foreach (array_slice($attachments, 1) as $attachment) {
                $message->attachments()->create(['path' => $attachment->store('chat-attachments', 'public'), 'name' => $attachment->getClientOriginalName(), 'mime' => $attachment->getMimeType(), 'size' => $attachment->getSize()]);
            }
            $conversation->touch();
            $conversation->participants()->where('user_id', '!=', $request->user()->id)->update(['hidden_at' => null]);
            return $message;
        });
        $message->load('attachments');
        // Persistence is committed before broadcasting. An outage must not turn
        // a successful save into a failed send (and invite duplicate retries).
        if ($staffId = $request->attributes->get('chat_staff_id')) {
            Log::info('Chat message sent through monitor dashboard Connecter.', [
                'staff_id' => $staffId, 'monitor_user_id' => $request->user()->id,
                'message_id' => $message->id, 'conversation_id' => $conversation->id,
            ]);
        }
        $realtime = $this->publish(new MessageSent($message, $conversation));
        return response()->json(['data' => $message, 'realtime' => $realtime], $message->wasRecentlyCreated ? 201 : 200);
    }

    public function read(Request $request, Conversation $conversation)
    {
        Gate::authorize('view', $conversation);
        $data = $request->validate(['through_id' => 'required|integer|min:1']);
        abort_unless($conversation->messages()->whereKey($data['through_id'])->exists(), 422);
        [$through, $changed, $readAt] = DB::transaction(function () use ($conversation, $request, $data) {
            Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $participant = $conversation->participants()->where('user_id', $request->user()->id)->firstOrFail();
            $through = max($participant->last_read_message_id, $data['through_id']);
            $readAt = now();
            $changed = $conversation->messages()->where('id', '<=', $through)
                ->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->update(['read_at' => $readAt]);
            $participant->update(['last_read_message_id' => $through]);
            return [$through, $changed, $readAt->toISOString()];
        });
        if ($changed) {
            $this->publish(new MessagesRead($conversation, (int) $request->user()->id, $through, $readAt));
        }
        return response()->json(['through_id' => $through, 'unread_count' => $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function clear(Request $request, Conversation $conversation)
    {
        Gate::authorize('view', $conversation);
        $now = now();
        DB::transaction(function () use ($conversation, $request, $now) {
            $conversation->participants()->where('user_id', $request->user()->id)->update(['cleared_at' => $now]);
            $conversation->messages()->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->update(['read_at' => $now]);
        });
        return response()->json(['cleared_at' => $now->toISOString()]);
    }

    public function destroy(Request $request, Conversation $conversation)
    {
        Gate::authorize('view', $conversation);
        $conversation->participants()->where('user_id', $request->user()->id)->update(['hidden_at' => now()]);
        return response()->noContent();
    }

    public function destroyMessage(Request $request, Conversation $conversation, Message $message)
    {
        Gate::authorize('view', $conversation);
        abort_unless($message->conversation_id === $conversation->id && $message->sender_id === $request->user()->id, 403);
        $messageId = $message->id;
        $message->delete();
        $conversation->touch();
        $this->publish(new MessageDeleted($conversation, $messageId));
        return response()->noContent();
    }

    private function publish(object $event): bool
    {
        try {
            event($event);
            return true;
        } catch (\Throwable $exception) {
            Log::warning('Chat broadcast unavailable; saved messages remain available through the API.', [
                'event' => $event::class, 'exception' => $exception::class,
            ]);
            return false;
        }
    }
}
