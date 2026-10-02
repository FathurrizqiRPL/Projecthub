<div>
    <label class="text-sm font-semibold text-slate-700">
        Nama Project
    </label>

    <input
        type="text"
        name="name"
        value="{{ old('name', $project->name ?? '') }}"
        class="mt-2 w-full rounded-xl border-slate-300
               focus:border-red-500 focus:ring-red-500"
        required
    >

    @error('name')
        <p class="mt-1 text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror
</div>

<div>
    <label class="text-sm font-semibold text-slate-700">
        Deskripsi
    </label>

    <textarea
        name="description"
        rows="5"
        class="mt-2 w-full rounded-xl border-slate-300
               focus:border-red-500 focus:ring-red-500"
    >{{ old('description', $project->description ?? '') }}</textarea>

    @error('description')
        <p class="mt-1 text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror
</div>

<div class="grid gap-5 md:grid-cols-2">

    <div>
        <label class="text-sm font-semibold text-slate-700">
            Tanggal Mulai
        </label>

        <input
            type="date"
            name="start_date"
            value="{{ old(
                'start_date',
                isset($project)
                    ? $project->start_date?->format('Y-m-d')
                    : ''
            ) }}"
            class="mt-2 w-full rounded-xl border-slate-300
                   focus:border-red-500 focus:ring-red-500"
        >

        @error('start_date')
            <p class="mt-1 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label class="text-sm font-semibold text-slate-700">
            Deadline
        </label>

        <input
            type="date"
            name="deadline"
            value="{{ old(
                'deadline',
                isset($project)
                    ? $project->deadline?->format('Y-m-d')
                    : ''
            ) }}"
            class="mt-2 w-full rounded-xl border-slate-300
                   focus:border-red-500 focus:ring-red-500"
        >

        @error('deadline')
            <p class="mt-1 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

</div>

<div>
    <label class="text-sm font-semibold text-slate-700">
        Status
    </label>

    <select
        name="status"
        class="mt-2 w-full rounded-xl border-slate-300
               focus:border-red-500 focus:ring-red-500"
    >
        @foreach ([
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ] as $value => $label)

            <option
                value="{{ $value }}"
                @selected(
                    old(
                        'status',
                        $project->status ?? 'draft'
                    ) === $value
                )
            >
                {{ $label }}
            </option>

        @endforeach
    </select>

    @error('status')
        <p class="mt-1 text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror
</div>


@if (! isset($project))

    {{-- Workflow --}}
    <div
        x-data="{
            type: '{{ old('workflow_type', 'default') }}',
            stages: @js(
                old(
                    'stages',
                    [
                        'Perencanaan',
                        'Development',
                        'Testing',
                        'Deployment'
                    ]
                )
            )
        }"
        class="rounded-2xl border border-slate-200
               bg-slate-50 p-5"
    >

        <div>
            <h3 class="font-semibold text-slate-900">
                Workflow Project
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Pilih workflow default atau buat tahapan sendiri.
            </p>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2">

            <label
                class="cursor-pointer rounded-xl border
                       border-slate-200 bg-white p-4"
            >
                <div class="flex items-start gap-3">
                    <input
                        type="radio"
                        name="workflow_type"
                        value="default"
                        x-model="type"
                        class="mt-1 text-red-600
                               focus:ring-red-500"
                    >

                    <div>
                        <p class="font-semibold text-slate-800">
                            Workflow Default
                        </p>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Perencanaan → Development →
                            Testing → Deployment
                        </p>
                    </div>
                </div>
            </label>

            <label
                class="cursor-pointer rounded-xl border
                       border-slate-200 bg-white p-4"
            >
                <div class="flex items-start gap-3">
                    <input
                        type="radio"
                        name="workflow_type"
                        value="custom"
                        x-model="type"
                        class="mt-1 text-red-600
                               focus:ring-red-500"
                    >

                    <div>
                        <p class="font-semibold text-slate-800">
                            Workflow Custom
                        </p>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Tentukan tahapan project secara manual.
                        </p>
                    </div>
                </div>
            </label>

        </div>

        <div
            x-show="type === 'custom'"
            x-cloak
            class="mt-5"
        >

            <div class="space-y-3">

                <template
                    x-for="(stage, index) in stages"
                    :key="index"
                >
                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-10 w-10 shrink-0
                                   items-center justify-center
                                   rounded-xl bg-white text-sm
                                   font-semibold text-slate-500"
                            x-text="index + 1"
                        ></div>

                        <input
                            type="text"
                            x-model="stages[index]"
                            :name="`stages[${index}]`"
                            placeholder="Nama tahap"
                            class="w-full rounded-xl border-slate-300
                                   focus:border-red-500
                                   focus:ring-red-500"
                        >

                        <button
                            type="button"
                            x-on:click="stages.splice(index, 1)"
                            x-show="stages.length > 1"
                            class="rounded-xl border
                                   border-red-200 px-3 py-2
                                   text-sm font-semibold
                                   text-red-600 hover:bg-red-50"
                        >
                            Hapus
                        </button>

                    </div>
                </template>

            </div>

            <button
                type="button"
                x-on:click="stages.push('')"
                class="mt-4 rounded-xl border
                       border-slate-200 bg-white
                       px-4 py-2 text-sm font-semibold
                       text-slate-600 hover:bg-slate-100"
            >
                + Tambah Tahap
            </button>

        </div>

        @error('workflow_type')
            <p class="mt-3 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror

        @error('stages')
            <p class="mt-3 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror

        @error('stages.*')
            <p class="mt-3 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror

    </div>

@endif
