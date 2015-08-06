# encoding: UTF-8

class CastorApp < Sinatra::Base

	def initialize
		super
		fn = File.join(Castor.root_dir, 'public', 'blank.png')
		@png = File.open(fn, 'rb').read
	end

	get '/ping' do
		'pong'
	end

	get '/:sid/:team/:question/:answer?' do |sid, team, question, answer|
		team = team.to_i
		question = question.to_i
		dirs = ("%010d" % team).scan(/.{1,2}/)
		FileUtils.mkdir_p(File.join('data', dirs))
		fn = File.join('data', dirs, question.to_s)
		attrs = { sid: sid, team: team, question: question }
		begin
			if answer
				b64 = answer.clone
				b64.gsub!('_', '/')
				b64.gsub!('-', '+')
				attrs[:answer] = Base64.strict_decode64(b64)
			else
				attrs[:answer] = nil
			end
		rescue ArgumentError
			attrs[:raw_answer] = answer
		end
		File.open(fn, 'a') do |f|
			f.puts(MultiJson.dump(attrs))
		end
		cache_control :no_cache
		content_type 'image/png'
		return @png
	end

	error do
		begin
			open(Castor[:error_log], 'a') do |f|
				f.puts(MultiJson.dump(
					date: DateTime.now.iso8601,
					ip: request.ip,
					url: request.url,
					params: params,
					referrer: request.referrer,
					user_agent: request.user_agent,
				))
			end
			halt 204
		rescue => ex
			warn "double error: #{ex}"
			warn ex.backtrace
		end
	end

end
