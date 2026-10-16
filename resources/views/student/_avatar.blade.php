{{-- Initials avatar: students have no profile photo. Expects $user and $size. --}}
<span class="avatar {{ $size }} mr-3 flex-shrink-0">
    <span class="avatar-title rounded-circle bg-primary">{{ str($user->name)->trim()->substr(0, 1)->upper() }}</span>
</span>
