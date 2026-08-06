@php($total = 7)
<div class="fmdc-progress" role="progressbar" aria-valuenow="{{ $step }}" aria-valuemin="1" aria-valuemax="{{ $total }}"
     aria-label="{{ __('wizard.step', ['current' => $step, 'total' => $total]) }}"
     title="{{ __('wizard.step', ['current' => $step, 'total' => $total]) }}">
    @for($i = 1; $i <= $total; $i++)
        <span class="{{ $i <= $step ? 'done' : '' }}"></span>
    @endfor
</div>
