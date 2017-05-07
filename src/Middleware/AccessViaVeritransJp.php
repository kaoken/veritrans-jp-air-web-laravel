<?php
/**
 * VeritransJp経由のアクセスか？ 判定するミドルウェア
 * なりすまし対策
 */
namespace Kaoken\VeritransJpAirWeb\Middleware;

class AccessViaVeritransJp
{
    /**
     * 着信要求を処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (
            $this->isReading($request) ||
            $this->runningUnitTests() ||
            $this->inExceptArray($request) ||
            $this->tokensMatch($request)
        ) {
            if( $request->ip() === '210.239.44.142')
                return $next($request);
        }

        abort(404);
    }
}