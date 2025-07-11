<script type="text/javascript">

    function getAttributeErrors(data) {
        var attribute_errors = "";
        if (typeof(data.attribute_errors) === 'object') {
            for(var attribute in data.attribute_errors) {
                attribute_errors += "<br/>[<b>"+attribute+"</b>]:";
                for (i = 0; i < data.attribute_errors[attribute].length; i++) {
                    attribute_errors += "<br/>&nbsp;&nbsp;"+data.attribute_errors[attribute][i];
                }
            };
        }

        return attribute_errors;
    }

    $(document).ready(function() {
    });

</script>
