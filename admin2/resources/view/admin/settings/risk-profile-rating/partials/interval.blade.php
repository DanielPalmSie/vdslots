<style>
    .error {
        color: red;
    }
</style>
<tr id="{{$parent->name}}-row">
    <td class="text-left">
        <div class="col-12">
            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" placeholder="Name" name="title" value="" form="{{$parent->name}}-create">
            </div>
        </div>
    </td>
    <td>
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Start</label>
                    <input type="text" class="form-control" placeholder="0" name="start" value="" form="{{$parent->name}}-create">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label>End</label>
                    <input type="text" class="form-control" placeholder="60" name="end" value="" form="{{$parent->name}}-create">
                </div>
            </div>
        </div>
    </td>
    <td>
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Score</label>
                    <input type="number" class="form-control" placeholder="10" name="score" value="" form="{{$parent->name}}-create">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label>Save new option</label>
                    <input type="hidden" name="is_interval" value="true" form="{{$parent->name}}-create">
                    <input type="submit" class="btn btn-warning form-control" form="{{$parent->name}}-create" id="{{$parent->name}}-create-submit" value="Save">
                    <button class="btn btn-default form-control d-none" disabled><i class="fa fa-refresh fa-spin"></i></button>
                </div>
            </div>
        </div>
    </td>
</tr>
<script>
    $(document).ready(function () {
        $("#{{$parent->name}}-create-submit").click(function (e) {
            e.preventDefault();

            let $self = $(this);
            let $parent = $("#{{$parent->name}}-row");
            let validation_errors = false;

            $parent.find(".error").remove();

            let $score = $parent.find("[name='score']");
            if (isNaN(parseInt($score.val())) || parseInt($score.val()) < 1 || parseInt($score.val()) > 100) {
                $score.parent().append("<span class='error'>The value must be between 1-100</span>");
                validation_errors = true;
            }

            $parent.find(".error").parent().find("input").off("click")
                .on("click focus keydown",function() {
                    $(this).parent().find(".error").remove();
                });

            if (!validation_errors) {
                $self.parent().find("input").addClass('hidden');
                $self.parent().find("button").removeClass('hidden');

                let $form = $("#" + $(this).attr('form'));
                let generic_error_message = "There was an internal error. Please reload the page and try again.";
                $.ajax({
                    url: $form.attr('action'),
                    method: $form.attr('method'),
                    data: $form.serializeArray()
                        .reduce(function (carry, el) {
                            carry[el.name] = el.value;
                            return carry
                        }, {}),
                    success: function (res) {
                        $self.parent().find("input").removeClass('hidden');
                        $self.parent().find("button").addClass('hidden');

                        if (res.status === 'success') {
                            alert("Press OK to apply the changes and reload the page.");
                            window.location.reload();
                        } else {
                            displayNotifyMessage("error", res.message ? res.message : generic_error_message);
                        }
                    }
                })
            }

        })
    })
</script>