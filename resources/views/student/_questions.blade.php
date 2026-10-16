{{-- Lesson Q&A, based on the theme's forum thread page. Expects $course, $lesson, $questions. --}}
@php($viewer = auth()->user())

<div class="card" id="duvidas">
    <div class="card-header">
        <h4 class="card-title">Dúvidas sobre esta aula</h4>
        <p class="card-subtitle">Pergunte para a Juliana. As perguntas e respostas ficam visíveis para todos os alunos do curso.</p>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('courses.lessons.questions.store', [$course, $lesson]) }}" class="d-flex">
            @csrf
            @include('student._avatar', ['user' => $viewer, 'size' => 'avatar-sm'])
            <div class="flex">
                <div class="form-group">
                    <label for="question-body" class="sr-only">Sua pergunta</label>
                    <textarea id="question-body" name="body" rows="3" maxlength="2000" required
                              class="form-control @error('body', 'question') is-invalid @enderror"
                              placeholder="Escreva sua pergunta para a Juliana...">{{ old('answering') ? '' : old('body') }}</textarea>
                    @error('body', 'question')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-success">Enviar pergunta</button>
            </div>
        </form>
    </div>

    @if ($questions->isNotEmpty())
        <div class="card-body border-top">
            <h4 class="mb-3">{{ $questions->total() }} {{ $questions->total() === 1 ? 'pergunta' : 'perguntas' }}</h4>

            @foreach ($questions as $question)
                <div class="lesson-question mb-4" id="pergunta-{{ $question->id }}">
                    <div class="d-flex">
                        @include('student._avatar', ['user' => $question->user, 'size' => 'avatar-sm'])
                        <div class="flex">
                            <p class="d-flex align-items-center flex-wrap mb-1">
                                <strong class="text-body mr-2">{{ $question->user->name }}</strong>
                                <small class="text-muted">{{ $question->created_at->diffForHumans() }}</small>
                                @if ($question->is_hidden)
                                    <span class="badge badge-warning ml-2">Oculta para os alunos</span>
                                @endif
                            </p>
                            <p class="mb-1 text-black-70 lesson-question__body">{{ $question->body }}</p>

                            @if ($question->user_id === $viewer->id && ! $question->isAnswered())
                                <form method="POST" action="{{ route('questions.destroy', $question) }}"
                                      onsubmit="return confirm('Excluir sua pergunta?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm text-muted p-0">excluir</button>
                                </form>
                            @elseif (! $question->isAnswered())
                                <small class="text-muted">Aguardando resposta da Juliana</small>
                            @endif
                        </div>
                    </div>

                    @foreach ($question->answers as $answer)
                        <div class="d-flex ml-sm-32pt mt-3 border rounded p-3 bg-light lesson-answer">
                            <span class="avatar avatar-sm mr-3">
                                <img src="{{ asset('assets/images/people/juliana.jpg') }}" alt="Juliana Balões" class="avatar-img rounded-circle">
                            </span>
                            <div class="flex">
                                <div class="d-flex align-items-center">
                                    <strong class="text-body">Juliana Balões</strong>
                                    <span class="badge badge-primary ml-2">Professora</span>
                                    <small class="ml-auto text-muted">{{ $answer->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mt-1 mb-0 text-black-70 lesson-question__body">{{ $answer->body }}</p>
                                @if ($viewer->isAdmin())
                                    <form method="POST" action="{{ route('admin.answers.destroy', $answer) }}"
                                          onsubmit="return confirm('Excluir esta resposta?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm text-muted p-0">excluir resposta</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if ($viewer->isAdmin())
                        @include('admin.questions._answer-form', ['question' => $question, 'compact' => true])
                    @endif
                </div>
            @endforeach

            {{ $questions->links() }}
        </div>
    @endif
</div>
