<?php

namespace Javaabu\Stats\Http\Controllers\Concerns;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Javaabu\Stats\Routing\StatsRoutes;
use Javaabu\Stats\Views\ViewRenderer;

/**
 * Validates the GET parameters of a stats page without redirecting: invalid
 * input shows the form with its errors and no chart.
 */
trait RendersStatsPage
{
    protected function views(): ViewRenderer
    {
        return app(ViewRenderer::class);
    }

    protected function statsUser(Request $request): ?Authorizable
    {
        $user = $request->user(StatsRoutes::guard($request));

        return $user instanceof Authorizable ? $user : null;
    }

    /**
     * The page's request, filled from the query string with defaults, and
     * its validator
     *
     * @template TRequest of FormRequest
     *
     * @param  class-string<TRequest>  $class
     * @param  array<string, mixed>  $defaults
     * @return array{0: TRequest, 1: ValidatorContract}
     */
    protected function pageRequest(Request $request, string $class, array $defaults): array
    {
        $input = array_merge($defaults, array_filter($request->query(), fn ($value) => $value !== null && $value !== ''));

        /** @var TRequest $stats_request */
        $stats_request = $class::createFrom($request);
        $stats_request->replace($input);
        $stats_request->setContainer(app())->setRedirector(app('redirect'));
        $stats_request->setUserResolver($request->getUserResolver());
        $stats_request->setRouteResolver($request->getRouteResolver());

        $validator = validator($input, $stats_request->rules(), [], $this->attributeNames());

        return [$stats_request, $validator];
    }

    protected ?MessageBag $page_errors = null;

    /**
     * Share the validator's errors with the views, as a failed form request would
     */
    protected function shareErrors(ValidatorContract $validator): MessageBag
    {
        $this->page_errors = $validator->errors();

        view()->share('errors', (new ViewErrorBag)->put('default', $this->page_errors));

        return $this->page_errors;
    }

    /**
     * The first error of each field, as Inertia sends errors
     *
     * @return array<string, string>
     */
    protected function errorMessages(): array
    {
        return array_map(fn (array $messages) => $messages[0], $this->page_errors?->toArray() ?? []);
    }

    /**
     * @return array<string, string>
     */
    protected function attributeNames(): array
    {
        return [
            'metric' => __('Stat'),
            'mode' => __('Group by'),
            'date_range' => __('Date range'),
            'date_from' => __('From'),
            'date_to' => __('To'),
            'compare_to' => __('Compare to'),
            'compare_date_from' => __('Compare from'),
            'compare_date_to' => __('Compare to date'),
            'categorical_values' => __('Choose categories'),
            'with' => __('Plot with'),
        ];
    }

    /**
     * Strings the pages show, translated
     *
     * @return array<string, string>
     */
    protected function pageStrings(string $title): array
    {
        return [
            'title' => $title,
            'export' => __('Download CSV'),
            'empty' => __('Choose a stat and a date range, then select Generate.'),
            'invalid' => __('Fix the errors in the form to see the stat.'),
        ];
    }
}
