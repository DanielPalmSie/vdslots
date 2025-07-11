<div class="{{ $containerClass }}">
    <label for="{{ $name }}">{{ $label }}</label>
    <input type="text" name="{{ $name }}" class="form-control" placeholder="{{ $placeholder }}" value="{{ $app['request_stack']->getCurrentRequest()->get($name) }}">
</div>
