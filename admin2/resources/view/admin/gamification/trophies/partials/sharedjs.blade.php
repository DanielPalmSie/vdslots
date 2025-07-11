<!-- TODO: Move this somewhere suitable since this is used by TournamentTemplates too. -->
<script type="text/javascript">
    function getAllNonModalButtons() {
        return $('button[id$="-btn"]');
    }

    $(document).ready(function() {

        // Quick fix to solve that the datepicker is not able to work with the 0000-00-00 date.
        $("input[data-provide='datepicker']").blur(function() {
            var start_date =  $(this).val();
            if (start_date == '0-11-30') {
                $(this).val('0000-00-00');
            }
        });

    });

</script>
