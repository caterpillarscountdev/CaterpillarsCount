$(document).ready(function () {

    /*
    $.ajax("/api/adminLogin.php", {
        type: "GET",
        success: function (data, status, xhr) {
            if(data.privilegeLevel) //master admin
                window.location.replace("master.html");
            else //site admin
                window.location.replace("home.html");
        }
    });
    */
    var $submitButton = $("#submit");
    $submitButton.click(function (e) {
        e.preventDefault();
        var $pw = $('#password');
        var $email = $('#email');
        console.log("log in pressed");

        if (!$email.val() || !$pw.val()) {
            $(".error").remove();
            $pw.css("border", "1px solid red");
            $email.css("border", "1px solid red");
            $pw.after("<p class = 'error'>Please fill in both fields before submitting!</p>");
            return;
        }

        var json_obj = {email: $email.val(), password: $pw.val()};
        $.ajax("/api/login.php",
                {type: "POST",
                    dataType: "json",
                    data: JSON.stringify(json_obj),
                    success: function (data, status, xhr) {
                        console.log("success");
                        console.log(data);
                        if (data.privilegeLevel >0 ) {
                            window.location.replace("master_home.html");

                        }else{
                            $pw.val("");
                            $email.val("");
                            $(".error").remove();
                            $email.css("border", "1px solid red");
                            $email.after("<p class = 'error'>User does not have Admin access.</p>")
                        }
                        if (data.validPw == 0) {
                            $pw.val("");
                            $(".error").remove();
                            $pw.css("border", "1px solid red");
                            $pw.after("<p class = 'error'> Password not correct!</p>");
                        }
                        if (data.active == 0) {
                            $pw.val("");
                            $email.val("");
                            $(".error").remove();
                            $email.css("border", "1px solid red");
                            $email.after("<p class = 'error'>User not activated!</p>");
                        }
                        if (data.validUser == 0) {
                            $pw.val("");
                            $email.val("");
                            $(".error").remove();
                            $email.css("border", "1px solid red");
                            $email.after("<p class = 'error'>User has been marked invalid. Please contact an administrator.</p>")
                        }
                    },
                    error: function (xhr, status) {
                        if (xhr.status == 404) {
                            $pw.val("");
                            $email.val("");
                            $(".error").remove();
                            $email.css("border", "1px solid red");
                            $email.after("<p class = 'error'>User not found!</p>")
                        }
                        else if (xhr.status == 403) {
                            $pw.val("");
                            $email.val("");
                            $(".error").remove();
                            $email.css("border", "1px solid red");
                            $email.after("<p class = 'error'>User is not an administrator!</p>");
                        }
                    }
                });
    });
});
