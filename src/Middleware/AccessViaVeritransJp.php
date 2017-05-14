<?php
/**
 * VeritransJp経由のアクセスか？ 判定するミドルウェア
 * ・なりすまし対策
 * このミドルウェアは、使うほどでも無いが・・・
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
            // ホスト cvs.veritrans.co.jp
            // IP　　 210.239.44.160
            if( $request->ip() === '210.239.44.160')
                return $next($request);
            else if( gethostbyaddr($request->ip()) === 'cvs.veritrans.co.jp')
                return $next($request);
        }

        return response('Unauthorized.', 404);
    }
}