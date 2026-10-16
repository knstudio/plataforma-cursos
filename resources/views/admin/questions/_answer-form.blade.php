{{-- Answer form. Expects $question; $compact shows a smaller version under each question on the lesson page. --}}
@php($isThisForm = (int) old('answering') === $question->id)

<form method="POST" action="{{ route('admin.answers.store', $question) }}" class="{{ ($compact ?? false) ? 'ml-sm-32pt mt-3' : '' }}">
    @csrf
    <input type="hidden" name="answering" value="{{ $question->id }}">
    <div class="form-group mb-2">
        <label for="answer-{{ $question->id }}" class="{{ ($compact ?? false) ? 'sr-only' : 'form-label' }}">Sua resposta</label>
        <textarea id="answer-{{ $question->id }}" name="body" rows="{{ ($compact ?? false) ? 2 : 5 }}" required
                  class="form-control @if ($isThisForm && $errors->answer->has('body')) is-invalid @endif"
                  placeholder="Responder {{ str($question->user->name)->before(' ') }}...">{{ $isThisForm ? old('body') : '' }}</textarea>
        @if ($isThisForm && $errors->answer->has('body'))
            <div class="invalid-feedback">{{ $errors->answer->first('body') }}</div>
        @endif
    </div>
    <button type="submit" class="btn btn-primary {{ ($compact ?? false) ? 'btn-sm' : '' }}">
        {{ $question->isAnswered() ? 'Complementar resposta' : 'Responder' }}
    </button>
</form>
