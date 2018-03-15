<form class="form-horizontal" role="form" method="POST" action="<?= site_url('auth/login') ?>">
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="sr-only" for="email">E-Mail Address</label>
                        <div class="input-group mb-2 mr-sm-2 mb-sm-0">
                            <div class="input-group-addon" style="width: 2.6rem"><i class="fa fa-at"></i></div>
                            <input type="text" name="identity" class="form-control" id="identity" placeholder="<?= $lang_place_ident ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="sr-only" for="password">Password</label>
                        <div class="input-group mb-2 mr-sm-2 mb-sm-0">
                            <div class="input-group-addon" style="width: 2.6rem"><i class="fa fa-key"></i></div>
                            <input type="password" name="password" class="form-control" id="password" placeholder="<?= $lang_pass_ident ?>" required>
                        </div>
                    </div>
                </div>
            </div>
                
            <div class="row">
                <div class="col-xs-9" style="padding-top: .35rem; margin-left:6em;">
                    <div class="check-box">
                    	<input class="form-check-input" name="remember" type="checkbox" id="rememberMe"> 
                        <label class="form-check-label"><?= $lang_remember_btn ?></label>
                    </div>

                </div>
            </div>

            <div class="row" style="padding-top: 1rem">
                <div class="col-md-3"></div>
                <div class="col-md-6 text-center">
                    <button type="submit" class="btn btn-outline-default"><i class="fas fa-sign-in-alt"></i> <?= $lang_login_btn ?></button>
                    <a class="btn btn-link" href="<?= site_url('auth/forgot_password') ?>"><?= $lang_forgot_btn ?></a>
                </div>
            </div>
        </form>
