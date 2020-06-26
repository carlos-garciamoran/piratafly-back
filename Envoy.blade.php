@servers(['web' => ['piratafly']])

@setup
    $app      = '/var/www/piratafly/api';
    $releases = '/var/www/piratafly/releases/api';
    $release  = $releases . '/' . date('YmdHis');
@endsetup

@story('deploy', ['on' => 'web'])
    clone_repo
    run_composer
    update_symlinks
    optimise_app
    update_permissions
    archive_previous_release
@endstory

@task('clone_repo')
    git clone git@github.com:carlosgmoran/piratafly-back.git {{ $release }};
    rm -r {{ $release }}/.git {{ $release }}/storage;
@endtask

@task('run_composer')
    echo "Running composer install...";
    cd {{ $release }};
    composer -q install --no-dev --no-scripts --optimize-autoloader;
@endtask

@task('update_symlinks')
    echo "Symlinking storage, .env, and app...";
    cd {{ $release }};
    ln -nfs /var/www/piratafly/storage {{ $release }}/storage;
    ln -nfs /var/www/piratafly/.env {{ $release }}/.env;
    ln -nfs {{ $release }} {{ $app }};
@endtask

@task('optimise_app')
    echo "Optimising app for production...";
    cd {{ $release }};
    php artisan optimize;
@endtask

@task('update_permissions')
    echo "Updating permissions and ownership...";
    find {{ $release }} -type f -exec chmod 640 {} \;
    find {{ $release }} -type d -exec chmod 750 {} \;
    sudo chown -R www-data:www-data {{ $release }};
@endtask

@task('archive_previous_release')
    echo "Archiving previous release...";
    previous_release=$(ls -d {{ $releases }}/* | sort -r | sed -n 2p);
    tar --exclude $previous_release/vendor -czf $previous_release.tar.gz $previous_release;
    sudo rm -r $previous_release;
@endtask
