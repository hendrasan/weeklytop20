<?php

describe('Helper Functions', function () {
    test('assets_url generates correct asset URL', function () {
        $result = assets_url('css/app.css');

        expect($result)->toBe(asset('assets/css/app.css'));
    });

    test('assets_url handles leading slash', function () {
        $result = assets_url('/css/app.css');

        expect($result)->toBe(asset('assets/css/app.css'));
    });

    test('uploads_url generates correct upload URL', function () {
        $result = uploads_url('images/photo.jpg');

        expect($result)->toBe(asset('uploads/images/photo.jpg'));
    });

    test('uploads_url handles leading slash', function () {
        $result = uploads_url('/images/photo.jpg');

        expect($result)->toBe(asset('uploads/images/photo.jpg'));
    });

    test('uploads_path returns file not found for non-existent file', function () {
        $result = uploads_path('non-existent-file.jpg');

        expect($result)->toBe('file not found');
    });

    test('currentRouteName returns current route name', function () {
        // Mock a route
        $response = $this->get('/');

        // The home route should have name 'home'
        $routeName = currentRouteName();

        expect($routeName)->toBe('home');
    });

    describe('getArrowIcon function', function () {
        test('returns star icon for new entry (no previous position)', function () {
            $result = getArrowIcon(5, null);

            expect($result)->toBe('has-text-warning fa-star');
        });

        test('returns up arrow for position improvement (lower number is better)', function () {
            $result = getArrowIcon(3, 7); // Moved from 7 to 3

            expect($result)->toBe('has-text-success fa-arrow-up');
        });

        test('returns down arrow for position decline (higher number is worse)', function () {
            $result = getArrowIcon(8, 4); // Moved from 4 to 8

            expect($result)->toBe('has-text-danger fa-arrow-down');
        });

        test('returns horizontal arrow for same position', function () {
            $result = getArrowIcon(5, 5); // Stayed at 5

            expect($result)->toBe('has-text-info fa-arrows-alt-h');
        });

        test('handles edge cases correctly', function () {
            // Moving to #1 from any position
            expect(getArrowIcon(1, 20))->toBe('has-text-success fa-arrow-up');

            // Moving from #1 to any other position
            expect(getArrowIcon(10, 1))->toBe('has-text-danger fa-arrow-down');

            // Staying at #1
            expect(getArrowIcon(1, 1))->toBe('has-text-info fa-arrows-alt-h');
        });

        test('works with zero as previous position', function () {
            // This might be an edge case in the data
            $result = getArrowIcon(5, 0);

            expect($result)->toBe('has-text-warning fa-star');
        });
    });
});
